<?php

declare(strict_types=1);

namespace My\Posts\Components\Front;

use My\Posts\Post;
use Osm\Framework\Blade\Component;

class ListItem extends Component
{
    public string $__template = 'posts::components.list-item';

    public function __construct(public Post $post) {
    }
}