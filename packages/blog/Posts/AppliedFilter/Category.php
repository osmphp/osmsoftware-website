<?php

declare(strict_types=1);

namespace Osm\Blog\Posts\AppliedFilter;

use Osm\Blog\Posts\AppliedFilter;
use Osm\Blog\Categories\Category as CategoryFile;
use Osm\Blog\Posts\Url;
use function Osm\__;
/**
 * @property CategoryFile $category
 */
class Category extends AppliedFilter
{
    protected function get_title(): string {
        return __("Category");
    }

    protected function get_value(): string {
        return $this->category->title;
    }

    protected function get_value_html(): string {
        return $this->category->title_html;
    }

    protected function get_clear_url(): string|Url {
        return $this->collection->url()->removeCategoryFilter($this);
    }
}