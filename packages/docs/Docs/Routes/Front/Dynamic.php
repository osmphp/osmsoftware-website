<?php

namespace Osm\Docs\Docs\Routes\Front;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Osm\Core\App;
use Osm\Core\BaseModule;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Docs\Docs\Book;
use Osm\Docs\Docs\Module;
use Osm\Docs\Docs\Version;
use Osm\Framework\Areas\Attributes\Area;
use Osm\Framework\Areas\Front;
use Osm\Framework\Http\DynamicRoute;
use Osm\Framework\Http\Routes\AddTrailingSlash;
use Osm\Framework\Settings\Hints\Settings;
use function FastRoute\simpleDispatcher;

/**
 * @property Settings $settings
 * @property string $prefix
 * @property Module $module
 * @property Book[] $books
 */
#[Area(Front::class)]
class Dynamic extends DynamicRoute
{
    protected function get_dispatcher(): Dispatcher {
        return simpleDispatcher(function (RouteCollector $r) {
            foreach ($this->books as $book) {
                $r->addGroup($book->url, function (RouteCollector $r)
                    use ($book)
                {
                    $this->bookRoutes($r, $book);
                });
            }
        });
    }

    protected function bookRoutes(RouteCollector $r, Book $book): void {
        if (count($book->versions) === 1) {
            foreach ($book->versions as $version) {
                $this->versionRoutes($r, $version);
            }
            return;
        }

        foreach ($book->versions as $version) {
            $r->addGroup("/{$version->name}", function (RouteCollector $r)
                use ($version)
            {
                $this->versionRoutes($r, $version);
            });
        }

        $r->get('', AddTrailingSlash::class);

        $versionNames = implode('|', array_keys($book->versions));
        $r->get("/{path:(?!{$versionNames}).*}",
            [RedirectToDefaultVersion::class => ['book_name' => $book->name]]);
    }

    protected function versionRoutes(RouteCollector $r, Version $version): void {
        $data = [
            'book_name' => $version->book->name,
            'version_name' => $version->name,
        ];

        $r->get('', [RedirectToHtml::class => array_merge($data, [
            'path' => 'index',
        ])]);
        $r->get('/', [RedirectToHtml::class => array_merge($data, [
            'path' => 'index',
        ])]);
        $r->get('/{path:.*\./}',
            [RedirectToHtml::class => array_merge($data)]);
        $r->get('/{path:.*\.(?:jpg|gif|png)}', [RenderImage::class => $data]);
        $r->get('/{path:.*\.html}', [RenderPage::class => $data]);
    }

    protected function get_settings(): \stdClass|Settings {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->settings;
    }

    protected function get_prefix(): string {
        return $this->settings->docs->url;
    }

    protected function get_module(): BaseModule {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->modules[Module::class];
    }

    protected function get_books(): array {
        return $this->module->books;
    }
}