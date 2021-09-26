<?php

declare(strict_types=1);

namespace Osm\Blog\Posts\Components\Front\Filter;

use Osm\Blog\Posts\Filter;
use Osm\Framework\Blade\Component;

class Date extends Component
{
    public string $__template = 'posts::components.filter.date';

    public function __construct(public Filter\Date $filter) {
    }
}