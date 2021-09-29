<?php

namespace Osm\Docs\Docs;

use Osm\Core\Object_;
use Osm\Core\Attributes\Serialized;

/**
 * @property string $name #[Serialized]
 * @property string $path #[Serialized] Absolute path to the parent
 *      directory of all the version directories
 * @property ?string $repo #[Serialized] Remote repository URL.
 *      If omitted, $path is considered not a cloned Git repo, but
 *      a local directory where the book pages are edited locally.
 * @property ?string $dir #[Serialized] A subdirectory inside $path
 *      where the book pages are stored. If omitted, it is assumed that
 *      $path stores the book pages.
 * @property Version[] $versions #[Serialized]
 */
class Book extends Object_
{

}