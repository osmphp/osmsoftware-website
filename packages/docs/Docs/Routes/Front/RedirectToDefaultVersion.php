<?php

namespace Osm\Docs\Docs\Routes\Front;

use Osm\Core\Exceptions\NotImplemented;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property string $path
 */
class RedirectToDefaultVersion extends BookRoute
{
    public function run(): Response {
        throw new NotImplemented($this);
    }
}