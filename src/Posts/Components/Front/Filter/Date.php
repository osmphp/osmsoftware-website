<?php

declare(strict_types=1);

namespace My\Posts\Components\Front\Filter;

use My\Posts\Filter;
use Osm\Framework\Blade\Component;

class Date extends Component
{
    public string $__template = 'posts::components.filter.date';

    public function __construct(public Filter\Date $filter) {
    }
}