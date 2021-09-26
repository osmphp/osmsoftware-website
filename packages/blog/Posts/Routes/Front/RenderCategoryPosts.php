<?php

declare(strict_types=1);

namespace Osm\Blog\Posts\Routes\Front;

use Osm\Blog\Posts\Posts;
use Osm\Framework\Http\Exceptions\NotFound;
use Osm\Framework\Http\Route;
use function Osm\view_response;
use Symfony\Component\HttpFoundation\Response;
use Osm\Blog\Posts\PageType;

/**
 * @property string $category
 */
class RenderCategoryPosts extends Route
{
    public function run(): Response {
        $pageType = PageType\Category::new([
            'category_url_key' => $this->category,
        ]);

        if (!$pageType->category) {
            throw new NotFound();
        }

        return view_response('posts::pages.category', [
            'posts' => Posts::new(['page_type' => $pageType]),
        ]);
    }

}