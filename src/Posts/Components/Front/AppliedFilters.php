<?php

declare(strict_types=1);

namespace My\Posts\Components\Front;

use My\Posts\Posts;
use Osm\Framework\Blade\Component;

class AppliedFilters extends Component
{
    public string $__template = 'posts::components.applied-filters';

    public function __construct(public Posts $posts) {
    }
}