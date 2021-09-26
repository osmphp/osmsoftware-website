<?php

declare(strict_types=1);

namespace Osm\Blog\Posts\PageType;

use Osm\Blog\Posts\PageType;

class Home extends PageType
{
    public bool $ignore_search_parameter = true;
}