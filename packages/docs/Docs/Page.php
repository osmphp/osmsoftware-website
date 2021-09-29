<?php

namespace Osm\Docs\Docs;

use Osm\Data\Markdown\File;

/**
 * @property Version $version
 */
class Page extends File
{
    protected function get_root_path(): string {
        return $this->version->root_path;
    }
}