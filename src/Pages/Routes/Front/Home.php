<?php

declare(strict_types=1);

namespace My\Pages\Routes\Front;

use Osm\Blog\Posts\Posts;
use Osm\Core\Attributes\Name;
use Osm\Framework\Areas\Attributes\Area;
use Osm\Framework\Areas\Front;
use Osm\Framework\Http\Route;
use Symfony\Component\HttpFoundation\Response;
use Osm\Blog\Posts\PageType;
use function Osm\view_response;

#[Area(Front::class), Name('GET /')]
class Home extends Route
{
    public function run(): Response {
        return view_response('pages::home', [
            'news' => Posts::new([
                'page_type' => PageType\Category::new([
                    'category_url_key' => 'news',
                ]),
            ])->first,
        ]);
    }
}