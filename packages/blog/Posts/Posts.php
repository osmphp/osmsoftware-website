<?php

declare(strict_types=1);

namespace Osm\Blog\Posts;

use Illuminate\Support\Collection;
use Osm\Core\App;
use Osm\Core\Object_;
use Osm\Framework\Db\Db;
use Osm\Framework\Search\Query;
use Osm\Framework\Search\Result;
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
 * @property Result $result
 * @property Result[] $facet_results
 * @property Db $db
 * @property Search $search
 * @property Collection $db_records
 * @property int $count
 * @property Post[] $files
 * @property Post[] $items
 * @property ?Post $first
 * @property Filter[] $filters
 * @property AppliedFilter[] $applied_filters
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

    protected function get_first(): ?Post {
        foreach ($this->result->ids as $id) {
            return $this->files[$id] ?? null;
        }

        return null;
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
        else {
            $query->limit(10000);
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

    protected function get_applied_filters(): array {
        $appliedFilters = [];

        foreach ($this->filters as $filter) {
            $appliedFilters = array_merge($appliedFilters,
                $filter->applied_filters);
        }

        return $appliedFilters;
    }
}