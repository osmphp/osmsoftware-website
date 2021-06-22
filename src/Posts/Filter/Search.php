<?php

declare(strict_types=1);

namespace My\Posts\Filter;

use My\Posts\AppliedFilter;
use My\Posts\Filter;

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
            ? [AppliedFilter\Search::new(['phrase' => $this->unparsed_value])]
            : [];
    }
}