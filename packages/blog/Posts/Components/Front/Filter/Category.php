<?php

declare(strict_types=1);

namespace Osm\Blog\Posts\Components\Front\Filter;

use Osm\Blog\Posts\Filter;
use Osm\Framework\Blade\Component;

class Category extends Component
{
    public string $__template = 'posts::components.filter.category';

    public function __construct(public Filter\Category $filter) {
    }
}