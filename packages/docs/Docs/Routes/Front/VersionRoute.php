<?php

namespace Osm\Docs\Docs\Routes\Front;

use Osm\Docs\Docs\Version;

/**
 * @property string $version_name
 * @property Version $version
 */
class VersionRoute extends BookRoute
{
    protected function get_version(): Version {
        return $this->book->versions[$this->version_name];
    }
}