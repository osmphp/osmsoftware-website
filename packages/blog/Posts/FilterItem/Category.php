<?php

declare(strict_types=1);

namespace Osm\Blog\Posts\FilterItem;

use Osm\Blog\Categories\Module as CategoryModule;
use Osm\Blog\Categories\Category as CategoryFile;
use Osm\Blog\Posts\AppliedFilter;
use Osm\Blog\Posts\FilterItem;
use Osm\Blog\Posts\Url;
use Osm\Core\App;
use Osm\Core\BaseModule;
use Osm\Core\Exceptions\NotImplemented;

/**
 * @property CategoryModule $category_module
 * @property ?CategoryFile $category
 * @property ?AppliedFilter\Category $applied_filter
 */
class Category extends FilterItem
{
    protected function get_title(): string {
        return $this->category->title;
    }

    protected function get_title_html(): string {
        return $this->category->title_html;
    }

    protected function get_add_url(): string|Url {
        return $this->filter->collection->url()
            ->addCategoryFilter($this->category);
    }

    protected function get_remove_url(): string|Url {
        return $this->filter->collection->url()
            ->removeCategoryFilter($this->applied_filter);
    }

    protected function get_applied(): bool {
        return $this->applied_filter !== null;
    }

    protected function get_category_module(): CategoryModule|BaseModule {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->modules[CategoryModule::class];
    }

    protected function get_category(): ?CategoryFile {
        return $this->category_module->categories[$this->value] ?? null;
    }

    protected function get_visible(): bool {
        return $this->category !== null;
    }

    protected function get_applied_filter()
        : AppliedFilter\Category|AppliedFilter|null
    {
        foreach ($this->filter->applied_filters as $appliedFilter) {
            if ($appliedFilter->category->url_key == $this->value) {
                return $appliedFilter;
            }
        }

        return null;
    }
}