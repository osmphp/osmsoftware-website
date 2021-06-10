<?php

declare(strict_types=1);

namespace My\Posts\Routes\Front;

use Osm\Framework\Http\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class AddTrailingSlash extends Route
{
    public function run(): Response {
        return new RedirectResponse(
            "{$this->http->base_url}{$this->http->path}/" .
            "{$this->http->request->server->get('QUERY_STRING')}", 301);
    }
}