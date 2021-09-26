<?php

declare(strict_types=1);

namespace Osm\Blog\Posts\PageType;

use Osm\Blog\Posts\PageType;

class Month extends PageType
{
    public bool $ignore_date_parameter = true;
}