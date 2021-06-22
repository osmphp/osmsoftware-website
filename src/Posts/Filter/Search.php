<?php

declare(strict_types=1);

namespace My\Posts\Filter;

use My\Posts\AppliedFilter;
use My\Posts\Filter;
use Osm\Framework\Search\Query;
use function Osm\url_encode;

/**
 */
class Search extends Filter
{
    protected function get_unparsed_value(): ?string {
        return $this->collection->page_type->ignore_search_parameter
            ? null
            : parent::get_unparsed_value();
    }

    protected function get_applied_filters(): array {
        return $this->unparsed_value
            ? [AppliedFilter\Search::new([
                'phrase' => $this->unparsed_value,
                'filter' => $this,
            ])]
            : [];
    }

    public function apply(Query $query): void {
        if (!empty($this->applied_filters)) {
            $query->search($this->applied_filters[0]->phrase);
        }
    }

    public function requestFacets(Query $query): void {
        // search filter has no facets to count
    }

    /**
     * @param AppliedFilter[] $appliedFilters
     * @return string
     */
    public function url(array $appliedFilters): string {
        return url_encode($appliedFilters[0]->phrase);
    }
}