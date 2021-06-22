<?php

declare(strict_types=1);

namespace My\Posts\Filter;

use My\Posts\AppliedFilter;
use My\Posts\Filter;
use My\Categories\Module as CategoryModule;
use Osm\Core\App;
use Osm\Core\BaseModule;

/**
 * @property CategoryModule $category_module
 */
class Category extends Filter
{
    protected function get_unparsed_value(): ?string {
        return $this->collection->page_type->ignore_category_parameter
            ? null
            : parent::get_unparsed_value();
    }

    protected function get_applied_filters(): array {
        if ($this->collection->page_type->category) {
            return [AppliedFilter\Category::new([
                'category' => $this->collection->page_type->category,
            ])];
        }

        $appliedFilters = [];

        if (!$this->unparsed_value) {
            return $appliedFilters;
        }

        foreach (explode(' ', $this->unparsed_value) as $urlKey) {
            if (isset($this->category_module->categories[$urlKey])) {
                $appliedFilters[$urlKey] = AppliedFilter\Category::new([
                    'category' => $this->category_module->categories[$urlKey],
                ]);
            }
        }

        return array_values($appliedFilters);
    }


    protected function get_category_module(): CategoryModule|BaseModule {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->modules[CategoryModule::class];
    }
}