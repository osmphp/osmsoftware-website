<?php

declare(strict_types=1);

namespace My\Posts\PageType;

use My\Posts\PageType;

class Home extends PageType
{
    public bool $ignore_search_parameter = true;
}