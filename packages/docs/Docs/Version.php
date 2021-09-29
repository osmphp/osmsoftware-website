<?php

namespace Osm\Docs\Docs;

use Osm\Core\Object_;
use Osm\Core\Attributes\Serialized;

/**
 * @property Book $book
 * @property string $name #[Serialized]
 * @property string $path #[Serialized] Absolute path to the local
 *      repository files
 * @property ?string $repo #[Serialized] Remote repository URL.
 *      If omitted, $path is considered not a cloned Git repo, but
 *      a local directory where the book pages are edited locally
 * @property ?string $branch #[Serialized] Remote repository branch.
 *      If omitted, $path is considered not a cloned Git repo, but
 *      a local directory where the book pages are edited locally
 * @property ?string $dir #[Serialized] A subdirectory inside $path
 *      where the book pages are stored. If omitted, it is assumed that
 *      $path stores the book pages
 *
 * @property string $root_path
 */
class Version extends Object_
{
    protected function get_root_path(): string {
        return $this->dir
            ? "{$this->path}/{$this->dir}"
            : $this->path;
    }

    protected function get_path(): string {
        return "{$this->book->path}/{$this->name}";
    }

    protected function get_repo(): string {
        return $this->book->repo;
    }

    protected function get_dir(): string {
        return $this->book->dir;
    }
}