<?php

declare(strict_types=1);

namespace My\Posts\Routes\Front;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Osm\Framework\Areas\Attributes\Area;
use Osm\Framework\Areas\Front;
use Osm\Framework\Http\Route;
use function FastRoute\simpleDispatcher;

/**
 * @property Dispatcher $dispatcher
 * @property string $prefix
 */
#[Area(Front::class)]
class Dynamic extends Route
{
    public function match(): ?Route {
        $dispatched = $this->dispatcher->dispatch(
            $this->http->request->getMethod(), $this->http->path);

        if ($dispatched[0] !== Dispatcher::FOUND) {
            return null;
        }

        $new = "{$dispatched[1]}::new";

        return $new($dispatched[2]);
    }

    protected function get_dispatcher(): Dispatcher {
        return simpleDispatcher(function (RouteCollector $r) {
            $r->addGroup($this->prefix, function (RouteCollector $r) {
                $this->collectRoutes($r);
            });
        });
    }

    protected function collectRoutes(RouteCollector $r): void {
        $r->get('', AddTrailingSlash::class);
        $r->get('/{year:\d+}', AddTrailingSlash::class);
        $r->get('/{year:\d+}/{month:\d+}', AddTrailingSlash::class);
        $r->get('/{category:(?!search)\w[^/]*}', AddTrailingSlash::class);

        $r->get('/', RenderAllPosts::class);
        $r->get('/search', RenderSearchResults::class);
        $r->get('/{year:\d+}/{month:\d+}/{url_key}.html',
            RenderPost::class);
        $r->get('/{year:\d+}/', RenderCategoryPosts::class);
        $r->get('/{year:\d+}/{month:\d+}/', RenderMonthPosts::class);
        $r->get('/{category:\w[^/]*}/', RenderCategoryPosts::class);
    }

    protected function get_prefix(): string {
        return '/blog';
    }
}