<?php

declare(strict_types=1);

namespace My\Base;

use Osm\App\App;
use Osm\Core\Attributes\Name;
use Osm\Core\BaseModule;

#[Name('my')]
class Module extends BaseModule
{
    public static ?string $app_class_name = App::class;

    public static array $requires = [
        \Osm\Framework\Http\Module::class,
        \Osm\Framework\Settings\Module::class,
        \Osm\Framework\Blade\Module::class,
        \Osm\Framework\Themes\Module::class,
    ];
}