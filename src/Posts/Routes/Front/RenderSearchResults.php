<?php

declare(strict_types=1);

namespace My\Posts\Routes\Front;

use My\Posts\Posts;
use Osm\Framework\Http\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use function Osm\view_response;
use My\Posts\PageType;

class RenderSearchResults extends Route
{
    public function run(): Response {
        $posts = Posts::new([
            'page_type' => PageType\Search::new(),
        ]);

        if (empty($posts->filters['q']->applied_filters)) {
            return new RedirectResponse((string)$posts->url()->removeSearch(),
                301);
        }

        return view_response('posts::pages.search', [
            'posts' => $posts,
        ]);
    }

}