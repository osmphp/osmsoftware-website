<?php

namespace Osm\Docs\Docs\Routes\Front;

use Osm\Core\Exceptions\NotImplemented;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property string $path
 */
class RedirectToHtml extends VersionRoute
{
    public function run(): Response {
        return new RedirectResponse(
            "{$this->http->base_url}{$this->http->path}index.html" .
            "{$this->http->request->server->get('QUERY_STRING')}", 301);
    }
}