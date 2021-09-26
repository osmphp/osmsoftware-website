<?php

declare(strict_types=1);

namespace Osm\Blog\Posts\FilterItem;

use Carbon\Carbon;
use Osm\Blog\Posts\AppliedFilter;
use Osm\Blog\Posts\FilterItem;
use Osm\Blog\Posts\Url;

/**
 * @property int $year
 * @property int $month
 * @property Carbon $date
 * @property ?AppliedFilter\Date $applied_filter
 */
class Month extends FilterItem
{
    protected function get_title(): string {
        return $this->date->format('M');
    }

    protected function get_add_url(): string|Url {
        return $this->filter->collection->url()
            ->addDateFilter($this->year, $this->month);
    }

    protected function get_remove_url(): string|Url {
        return $this->filter->collection->url()
            ->removeDateFilter($this->applied_filter);
    }

    protected function get_applied(): bool {
        return $this->applied_filter !== null;
    }

    protected function get_applied_filter()
        : AppliedFilter\Date|AppliedFilter|null
    {
        foreach ($this->filter->applied_filters as $appliedFilter) {
            if ($appliedFilter->year == $this->year &&
                $appliedFilter->month == $this->month)
            {
                return $appliedFilter;
            }
        }

        return null;
    }

    protected function get_date(): Carbon {
        list($year, $month) = explode('-', $this->value);

        return Carbon::createFromDate((int)$year, (int)$month,1);
    }

    protected function get_year(): int {
        return $this->date->year;
    }

    protected function get_month(): int {
        return $this->date->month;
    }
}