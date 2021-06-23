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
 */
class Date extends AppliedFilter
{
    protected function get_title(): string {
        return $this->month ? __("Month"): __("Year");
    }

    protected function get_value(): string {
        if ($this->month) {
            $date = Carbon::createFromDate($this->year, $this->month,1);

            return $date->format("Y-m");
        }
        else {
            return (string)$this->year;
        }
    }


    protected function get_clear_url(): string|Url {
        return $this->collection->url()->removeDateFilter($this);
    }
}