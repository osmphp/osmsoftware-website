<?php

namespace Osm\Docs\Docs\Routes\Front;

use Osm\Core\Exceptions\NotImplemented;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use function Osm\__;

/**
 * @property string $path
 */
class RedirectToDefaultVersion extends BookRoute
{
    public function run(): Response {
        if (!str_starts_with($this->http->path, $this->book->url)) {
            throw new \Exception(__(
                "The resource ':resource' is not part of the book ':book'", [
                    'resource' => $this->http->path,
                    'book' => $this->book->url,
                ]
            ));
        }

        return new RedirectResponse(
            "{$this->http->base_url}{$this->book->url}" .
            "/{$this->book->default_version_name}" .
            mb_substr($this->http->path, mb_strlen($this->book->url)) .
            "{$this->http->request->server->get('QUERY_STRING')}", 301);
    }
}