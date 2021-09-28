<?php

namespace Osm\Data\Indexing;

use Osm\Core\BaseModule;

class Module extends BaseModule
{
    public static array $requires = [
        \Osm\Framework\Cache\Module::class,
        \Osm\Framework\Console\Module::class,
        \Osm\Framework\Translations\Module::class,
    ];
}