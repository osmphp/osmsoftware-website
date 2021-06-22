<?php

declare(strict_types=1);

namespace My\Posts\PageType;

use My\Posts\PageType;

class Year extends PageType
{
    public bool $ignore_date_parameter = true;
}