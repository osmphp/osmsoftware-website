<?php

declare(strict_types=1);

namespace My\Posts\Filter;

use My\Posts\AppliedFilter;
use My\Posts\Filter;
use My\Categories\Module as CategoryModule;
use Osm\Core\App;
use Osm\Core\BaseModule;
use Osm\Framework\Search\Query;
use function Osm\url_encode;

/**
 * @property CategoryModule $category_module
 */
class Category extends Filter
{
    protected function get_unparsed_value(): ?string {
        return $this->collection->page_type->ignore_category_parameter
            ? null
            : parent::get_unparsed_value();
    }

    protected function get_applied_filters(): array {
        if ($this->collection->page_type->category) {
            return [AppliedFilter\Category::new([
                'category' => $this->collection->page_type->category,
                'filter' => $this,
            ])];
        }

        $appliedFilters = [];

        if (!$this->unparsed_value) {
            return $appliedFilters;
        }

        foreach (explode(' ', $this->unparsed_value) as $urlKey) {
            if (isset($this->category_module->categories[$urlKey])) {
                $appliedFilters[$urlKey] = AppliedFilter\Category::new([
                    'category' => $this->category_module->categories[$urlKey],
                    'filter' => $this,
                ]);
            }
        }

        return array_values($appliedFilters);
    }


    protected function get_category_module(): CategoryModule|BaseModule {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->modules[CategoryModule::class];
    }

    public function apply(Query $query): void {
        $urlKeys = [];
        foreach ($this->applied_filters as $appliedFilter) {
            $urlKeys[] = $appliedFilter->category->url_key;
        }

        $query->where('category', 'in', $urlKeys);
    }

    public function requestFacets(Query $query): void {
        $query->facetBy('category');
    }

    protected function get_require_facet_query(): bool {
        return !empty($this->applied_filters) &&
            !$this->collection->page_type->category;
    }

    /**
     * @param AppliedFilter\Category[] $appliedFilters
     * @return string
     */
    public function url(array $appliedFilters): string {
        $url = '';

        foreach ($this->sort($appliedFilters) as $appliedFilter) {
            if ($url) {
                $url .= ' ';
            }

            $url .= $appliedFilter->category->url_key;
        }

        return url_encode($url);

    }

    protected function sort(array $appliedFilters): array {
        usort($appliedFilters,
            fn(AppliedFilter\Category $a, AppliedFilter\Category $b) =>
                $a->category->url_key <=> $b->category->url_key);

        return $appliedFilters;
    }
}