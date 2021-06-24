<?php

declare(strict_types=1);

namespace My\Posts\Filter;

use Carbon\Carbon;
use My\Posts\AppliedFilter;
use My\Posts\Filter;
use My\Posts\FilterItem;
use Osm\Framework\Search\Hints\Result\Facet;
use Osm\Framework\Search\Query;
use Osm\Framework\Search\Where;
use function Osm\url_encode;
use function Osm\__;

/**
 * @property \stdClass|Facet $year_facet
 * @property \stdClass|Facet $month_facet
 */
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
                'filter' => $this,
            ])];
        }

        $appliedFilters = [];

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
                    'filter' => $this,
                ]);
            }
        }

        return $this->normalize($appliedFilters);
    }

    /**
     * @param AppliedFilter\Date[] $appliedFilters
     * @return AppliedFilter\Date[]
     */
    public function normalize(array $appliedFilters): array {
        $wholeYears = [];

        foreach ($appliedFilters as $appliedFilter) {
            if (!$appliedFilter->month) {
                $wholeYears[] = $appliedFilter->year;
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

        usort($appliedFilters,
            function(AppliedFilter\Date $a, AppliedFilter\Date $b) {
                if (($result = ($a->year <=> $b->year)) !== 0) {
                    return $result;
                }

                if (!$a->month) {
                    return -1;
                }

                if (!$b->month) {
                    return 1;
                }

                return $a->month <=> $b->month;
            }
        );

        return array_values($appliedFilters);
    }

    protected function get_require_facet_query(): bool {
        return !empty($this->applied_filters);
    }

    public function apply(Query $query): void {
        $query->or(function(Where $clause) {
            foreach ($this->applied_filters as $appliedFilter) {
                if ($appliedFilter->month) {
                    $date = Carbon::createFromDate($appliedFilter->year,
                        $appliedFilter->month,1);

                    $clause->where('month', '=',
                        $date->format("Y-m"));
                }
                else {
                    $clause->where('year', '=',
                        $appliedFilter->year);
                }
            }
        });
    }

    public function requestFacets(Query $query): void {
        $query
            ->facetBy('year')
            ->facetBy('month');
    }

    public function url(array $appliedFilters): string {
        $url = '';

        foreach ($this->normalize($appliedFilters) as $appliedFilter) {
            if ($url) {
                $url .= ' ';
            }

            if ($appliedFilter->month) {
                $date = Carbon::createFromDate($appliedFilter->year,
                    $appliedFilter->month,1);
                $url .= $date->format("Y-m");
            }
            else {
                $url .= $appliedFilter->year;
            }
        }

        return url_encode($url);

    }

    protected function get_component(): string {
        return 'posts::filter.date';
    }

    protected function get_year_facet(): \stdClass|Facet {
        return $this->require_facet_query
            ? $this->collection->facet_results['date']->facets['year']
            : $this->collection->result->facets['year'];
    }

    protected function get_month_facet(): \stdClass|Facet {
        return $this->require_facet_query
            ? $this->collection->facet_results['date']->facets['month']
            : $this->collection->result->facets['month'];
    }

    protected function get_items(): array {
        $yearItems = [];

        foreach ($this->year_facet->counts as $facetItem) {
            $yearItems[$facetItem->value] = FilterItem\Year::new([
                'filter' => $this,
                'value' => $facetItem->value,
                'count' => $facetItem->count,
                'months' => [],
            ]);
        }

        uasort($yearItems, fn(FilterItem\Year $a, FilterItem\Year $b)
            => -1 * ($a->value <=> $b->value));

        foreach ($this->month_facet->counts as $facetItem) {
            $monthItem = FilterItem\Month::new([
                'filter' => $this,
                'value' => $facetItem->value,
                'count' => $facetItem->count,
            ]);

            if (!isset($yearItems[$monthItem->year])) {
                continue;
            }

            $yearItems[$monthItem->year]->months[$monthItem->month - 1] = $monthItem;
        }

        foreach ($yearItems as $yearItem) {
            $months = [];

            for ($month = 0; $month < 12; $month++) {
                $months[] = $yearItem->months[$month] ?? FilterItem\Month::new([
                    'filter' => $this,
                    'value' => $yearItem->value . '-' . ($month + 1),
                    'count' => 0,
                ]);
            }

            $yearItem->months = $months;
        }

        return array_values($yearItems);
    }

    protected function get_title(): string {
        return __("Archive");
    }
}