<?php

namespace Osm\Docs\Docs;

use Osm\App\App;
use Osm\Core\BaseModule;

class Module extends BaseModule
{
    public static ?string $app_class_name = App::class;

    public static array $requires = [
        \Osm\Data\Indexing\Module::class,
        \Osm\Data\Markdown\Module::class,
    ];
}