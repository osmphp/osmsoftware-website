<?php

declare(strict_types=1);

namespace Osm\Blog\Posts\Routes\Front;

use Osm\Blog\Posts\Posts;
use Osm\Framework\Http\Exceptions\NotFound;
use Osm\Framework\Http\Route;
use Symfony\Component\HttpFoundation\Response;
use function Osm\view_response;
use Osm\Blog\Posts\PageType;

/**
 * @property string $year
 */
class RenderYearPosts extends Route
{
    public function run(): Response {
        return view_response('posts::pages.year', [
            'posts' => Posts::new(['page_type' => PageType\Year::new([
                'year' => (int)$this->year,
            ])]),
        ]);
    }

}