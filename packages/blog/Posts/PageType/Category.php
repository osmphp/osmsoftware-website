<?php

declare(strict_types=1);

namespace Osm\Blog\Posts\PageType;

use Osm\Blog\Posts\PageType;
use Osm\Blog\Categories\Module as CategoryModule;
use Osm\Blog\Categories\Category as CategoryFile;
use Osm\Core\App;
use Osm\Core\BaseModule;

/**
 * @property string $category_url_key
 * @property CategoryModule $category_module
 * @property ?CategoryFile $category
 */
class Category extends PageType
{
    public bool $ignore_category_parameter = true;

    protected function get_category(): ?CategoryFile {
        return $this->category_module->categories[$this->category_url_key]
            ?? null;
    }

    protected function get_category_module(): CategoryModule|BaseModule {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->modules[CategoryModule::class];
    }
}