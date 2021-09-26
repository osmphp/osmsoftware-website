<?php

declare(strict_types=1);

namespace Osm\Blog\Posts;

use Osm\App\App;
use Osm\Core\Attributes\Name;
use Osm\Core\BaseModule;

#[Name('posts')]
class Module extends BaseModule
{
    public static ?string $app_class_name = App::class;

    public static array $requires = [
        \My\Base\Module::class,
        \Osm\Data\Markdown\Module::class,
        \Osm\Blog\Categories\Module::class,
    ];
}