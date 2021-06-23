<?php

declare(strict_types=1);

namespace My\Posts;

use My\Categories\Category;
use Osm\Core\Object_;

/**
 * @property Posts $collection
 * @property array $url_state
 * @property string $prefix
 */
class Url extends Object_ implements \Stringable
{
    public function addCategoryFilter(Category $category): static {
        $this->url_state['category'][] = AppliedFilter\Category::new([
            'category' => $category,
            'filter' => $this->collection->filters['category'],
        ]);

        return $this;
    }

    public function addDateFilter(int $year, int $month = null): static {
        $this->url_state['date'][] = AppliedFilter\Date::new([
            'year' => $year,
            'month' => $month,
            'filter' => $this->collection->filters['date'],
        ]);

        return $this;
    }

    public function addSearch(string $phrase): static {
        $this->removeSearch();

        $this->url_state['q'][] = AppliedFilter\Search::new([
            'phrase' => $phrase,
            'filter' => $this->collection->filters['q'],
        ]);

        return $this;
    }

    public function removeCategoryFilter(AppliedFilter\Category $appliedFilter)
        : static
    {
        foreach (array_keys($this->url_state['category']) as $key) {
            if ($this->url_state['category'][$key] === $appliedFilter) {
                array_splice($this->url_state['category'], $key, 1);
                break;
            }
        }

        return $this;
    }

    public function removeAllCategoryFilters(): static {
        $this->url_state['category'] = [];

        return $this;
    }

    public function removeDateFilter(AppliedFilter\Date $appliedFilter)
        : static
    {
        foreach (array_keys($this->url_state['date']) as $key) {
            if ($this->url_state['date'][$key] === $appliedFilter) {
                array_splice($this->url_state['date'], $key, 1);
                break;
            }
        }

        return $this;
    }

    public function removeAllDateFilters(): static {
        $this->url_state['date'] = [];

        return $this;
    }

    public function removeSearch(): static {
        $this->url_state['q'] = [];

        return $this;
    }

    public function removeAllFilters(): static {
        return $this
            ->removeAllCategoryFilters()
            ->removeAllDateFilters()
            ->removeSearch();
    }

    protected function route(): string {
        if (count($this->url_state['category']) == 1) {
            /* @var AppliedFilter\Category $appliedFilter */
            $appliedFilter = $this->url_state['category'][0];

            $this->url_state['category'] = [];

            return "{$this->prefix}/{$appliedFilter->category->url_key}/";
        }

        if (count($this->url_state['date']) == 1) {
            /* @var AppliedFilter\Date $appliedFilter */
            $appliedFilter = $this->url_state['date'][0];

            $this->url_state['date'] = [];

            return $appliedFilter->month
                ? "{$this->prefix}/{$appliedFilter->year}/{$appliedFilter->month}/"
                : "{$this->prefix}/{$appliedFilter->year}/";
        }

        return count($this->url_state['q']) == 1
            ? "{$this->prefix}/search"
            : "{$this->prefix}/";
    }

    protected function query(): string {
        $query = [];

        foreach ($this->url_state as $key => $appliedFilters) {
            if (empty($appliedFilters)) {
                continue;
            }

            $query[] = $key . '=' .
                $this->collection->filters[$key]->url($appliedFilters);
        }

        return empty($query)
            ? ''
            : '?' . implode('&', $query);
    }

    public function __toString(): string {
        return "{$this->collection->base_url}{$this->route()}{$this->query()}";
    }

    protected function get_prefix(): string {
        return '/blog';
    }
}