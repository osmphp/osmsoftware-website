<?php

declare(strict_types=1);

namespace My\Categories;

use Osm\App\App;
use Osm\Core\Attributes\Name;
use Osm\Core\BaseModule;
use Osm\Framework\Cache\Attributes\Cached;

/**
 * @property string $root_path
 * @property Category[] $categories #[Cached('blog_categories')]
 */
#[Name('categories')]
class Module extends BaseModule
{
    public static ?string $app_class_name = App::class;

    public static array $requires = [
        \My\Base\Module::class,
        \My\Markdown\Module::class,
    ];

    protected function get_categories(): array {
        $categories = [];

        foreach (glob("{$this->root_path}/*.md") as $absolutePath) {
            $category = Category::new([
                'path' => mb_substr($absolutePath,
                    mb_strlen("{$this->root_path}/")),
            ]);

            $categories[$category->url_key] = $category;
        }

        uasort($categories,
            fn(Category $a, Category $b) => $a->sort_order <=> $b->sort_order);

        return $categories;
    }

    protected function get_root_path(): string {
        global $osm_app; /* @var App $osm_app */

        return "{$osm_app->paths->data}/posts__categories";
    }

}