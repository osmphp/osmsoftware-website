<?php

declare(strict_types=1);

namespace My\Posts;

use Illuminate\Support\Collection;
use My\Posts\Hints\Category;
use Osm\Core\App;
use Osm\Core\BaseModule;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Core\Object_;
use Osm\Framework\Db\Db;
use Osm\Framework\Search\Query;
use Osm\Framework\Search\Result;
use My\Categories\Module as CategoryModule;
use My\Categories\Category as CategoryFile;
use Osm\Framework\Search\Search;

/**
 * @property PageType $page_type
 * @property array $http_query
 * @property ?int $offset
 * @property ?int $limit
 * @property string $base_url
 *
 * @property Query $query
 * @property Query[] $facet_queries
 * @property ?string $current_category
 * @property Result $result
 * @property Result[] $facet_results
 * @property Db $db
 * @property Search $search
 * @property Collection $db_records
 * @property int $count
 * @property Post[] $files
 * @property Post[] $items
 * @property Category[]|null $categories
 * @property CategoryModule $category_module
 * @property Filter[] $filters
 * @property string $url_state
 */
class Posts extends Object_
{
    protected function get_result() {
        return $this->query->get();
    }

    protected function get_count(): int {
        return $this->result->count;
    }

    protected function get_db(): Db {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->db;
    }

    protected function get_db_records(): Collection {
        return $this->db->table('posts')
            ->whereIn('id', $this->result->ids)
            ->get(['id', 'path']);
    }

    protected function get_files(): Collection {
        return $this->db_records
            ->keyBy('id')
            ->map(fn($post) => Post::new(['path' => $post->path]))
            ->filter(fn(Post $file) => $file->exists);
    }

    protected function get_items(): array {
        $items = [];

        foreach ($this->result->ids as $id) {
            $items[$id] = $this->files[$id] ?? null;
        }

        return $items;
    }

    protected function get_categories(): ?array {
        if (empty($this->result->facets['category']->counts)) {
            return null;
        }

        $counts = $this->result->facets['category']->counts;
        $categories = [];

        foreach ($counts as $count) {
            if (isset($this->category_module->categories[$count->value])) {
                $category = $this->category_module->categories[$count->value];

            }
            else {
                $category = CategoryFile::new([
                    'title' => $count->value,
                    'title_html' => $count->value,
                    'url_key' => $count->value,
                    'sort_order' => null,
                ]);
            }
            $categories[] = (object)[
                'title' => $category->title,
                'title_html' => $category->title_html,
                'url' => $category->url(),
                'count' => $count->count,
                'current' => $category->url_key === $this->current_category,
                'sort_order' => $category->sort_order,
            ];
        }

        usort($categories,
            function(\stdClass|Category $a, \stdClass|Category $b) {
                if ($a->sort_order === null) {
                    return 1;
                }

                if ($b->sort_order === null) {
                    return -1;
                }

                return $a->sort_order <=> $b->sort_order;
            });

        return $categories;
    }

    protected function get_category_module(): CategoryModule|BaseModule {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->modules[CategoryModule::class];
    }

    protected function get_filters(): array {
        return [
            'q' => Filter\Search::new([
                'name' => 'q',
                'collection' => $this,
            ]),
            'category' => Filter\Category::new([
                'name' => 'category',
                'collection' => $this,
            ]),
            'date' => Filter\Date::new([
                'name' => 'date',
                'collection' => $this,
            ]),
        ];
    }

    protected function get_http_query(): array {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->http->query;
    }

    protected function get_base_url(): string {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->http->base_url;
    }

    protected function get_query(): Query {
        $query = $this->search->index('posts');

        foreach ($this->filters as $filter) {
            if (!empty($filter->applied_filters)) {
                $filter->apply($query);
            }

            if (!$filter->require_facet_query && !$this->limit) {
                $filter->requestFacets($query);
            }
        }

        if (!$this->filters['q']->unparsed_value) {
            $query->orderBy('created_at', desc: true);
        }

        if ($this->offset) {
            $query->offset($this->offset);
        }

        if ($this->limit) {
            $query->limit($this->limit);
        }

        return $query;
    }


    protected function get_search(): Search {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->search;
    }

    protected function get_facet_queries(): array {
        $queries = [];

        foreach ($this->filters as $filter) {
            if ($filter->require_facet_query && !$this->limit) {
                $query = $this->createFacetQuery($filter);

                $filter->requestFacets($query);

                $queries[$filter->name] = $query;
            }
        }

        return $queries;
    }

    protected function createFacetQuery(Filter $filterToBeOmitted): Query {
        $query = $this->search->index('posts');

        foreach ($this->filters as $filter) {
            if ($filter === $filterToBeOmitted) {
                continue;
            }

            if (empty($filter->applied_filters)) {
                continue;
            }

            $filter->apply($query);
        }

        return $query;
    }

    protected function get_facet_results(): array {
        return array_map(fn(Query $query) => $query->get(),
            $this->facet_queries);
    }

    public function url(): Url {
        return Url::new([
            'collection' => $this,
            'url_state' => $this->url_state,
        ]);
    }

    protected function get_url_state(): array {
        return array_map(fn(Filter $filter) => $filter->applied_filters,
            $this->filters);
    }
}