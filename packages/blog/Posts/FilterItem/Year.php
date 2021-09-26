<?php

declare(strict_types=1);

namespace Osm\Blog\Posts\FilterItem;

use Osm\Blog\Posts\AppliedFilter;
use Osm\Blog\Posts\FilterItem;
use Osm\Blog\Posts\Url;

/**
 * @property ?AppliedFilter\Date $applied_filter
 */
class Year extends FilterItem
{
    protected function get_title(): string {
        return (string)$this->value;
    }

    protected function get_add_url(): string|Url {
        return $this->filter->collection->url()
            ->addDateFilter($this->value);
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
            if ($appliedFilter->year == $this->value && !$appliedFilter->month) {
                return $appliedFilter;
            }
        }

        return null;
    }
}