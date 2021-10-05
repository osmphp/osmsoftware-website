<?php

namespace Osm\Data\Markdown;

use Osm\Core\Exceptions\NotImplemented;
use Osm\Core\Object_;

/**
 * @property string $name
 */
class Placeholder extends Object_
{
    public function render(File $file): ?string {
        throw new NotImplemented($this);
    }
}