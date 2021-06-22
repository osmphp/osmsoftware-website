<?php

declare(strict_types=1);

namespace My\Posts\Filter;

use My\Posts\AppliedFilter;
use My\Posts\Filter;

class Date extends Filter
{
    const PATTERN = '/(?<year>\d+)(?:-(?<month>\d+))?/';

    protected function get_unparsed_value(): ?string {
        return $this->collection->page_type->ignore_date_parameter
            ? null
            : parent::get_unparsed_value();
    }

    protected function get_applied_filters(): array {
        if ($this->collection->page_type->year) {
            return [AppliedFilter\Date::new([
                'year' => $this->collection->page_type->year,
                'month' => $this->collection->page_type->month,
            ])];
        }

        $appliedFilters = [];
        $wholeYears = [];

        if (!$this->unparsed_value) {
            return $appliedFilters;
        }

        foreach (explode(' ', $this->unparsed_value) as $date) {
            if (preg_match(static::PATTERN, $date, $match)) {
                $key = (int)$match['year'] .
                    (isset($match['month'])
                        ? '-' . (int)$match['month']
                        : '');

                $appliedFilters[$key] = $appliedFilter = AppliedFilter\Date::new([
                    'year' => (int)$match['year'],
                    'month' => isset($match['month'])
                        ? (int)$match['month']
                        : null,
                ]);

                if (!isset($match['month'])) {
                    $wholeYears[] = (int)$match['year'];
                }
            }
        }

        foreach ($wholeYears as $wholeYear) {
            foreach (array_keys($appliedFilters) as $key) {
                if (!$appliedFilters[$key]->month) {
                    continue;
                }

                if ($appliedFilters[$key]->year != $wholeYear) {
                    continue;
                }

                unset($appliedFilters[$key]);
            }
        }

        return array_values($appliedFilters);
    }
}