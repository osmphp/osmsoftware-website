<?php

declare(strict_types=1);

namespace My\Posts\AppliedFilter;

use Carbon\Carbon;
use My\Posts\AppliedFilter;
use My\Posts\Url;
use function Osm\__;

/**
 * @property int $year
 * @property ?int $month
 * @property Carbon $date
 */
class Date extends AppliedFilter
{
    protected function get_title(): string {
        return $this->month ? __("Month"): __("Year");
    }

    protected function get_value(): string {
        return $this->month
            ? $this->date->format("Y M")
            : (string)$this->year;
    }


    protected function get_clear_url(): string|Url {
        return $this->collection->url()->removeDateFilter($this);
    }

    protected function get_date(): Carbon {
        return Carbon::createFromDate($this->year, $this->month,1);
    }
}