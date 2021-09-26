<?php

declare(strict_types=1);

namespace Osm\Blog\Posts\Routes\Front;

use Osm\Blog\Posts\PageType;
use Osm\Blog\Posts\Posts;
use Osm\Core\App;
use Osm\Framework\Http\Route;
use Osm\Framework\Search\Search;
use Symfony\Component\HttpFoundation\Response;
use function Osm\view_response;

class RenderAllPosts extends Route
{
    public function run(): Response {
        return view_response('posts::pages.all', [
            'posts' => Posts::new([
                'page_type' => PageType\Home::new(),
            ]),
        ]);
    }
}