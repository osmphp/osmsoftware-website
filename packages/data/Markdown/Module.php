<?php

declare(strict_types=1);

namespace Osm\Data\Markdown;

use Osm\Core\BaseModule;

class Module extends BaseModule
{
    public static array $requires = [
        \Osm\Framework\Translations\Module::class,
    ];
}