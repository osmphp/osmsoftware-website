<?php

namespace Osm\Docs\Docs\Hints\Settings;

/**
 * @property string $path Absolute path to the parent
 *      directory of all the version directories
 * @property ?string $repo Remote repository URL.
 *      If omitted, $path is considered not a cloned Git repo, but
 *      a local directory where the book pages are edited locally.
 * @property ?string $dir A subdirectory inside $path
 *      where the book pages are stored. If omitted, it is assumed that
 *      $path stores the book pages.
 * @property string $url The base URL of the book, relative
 *      to the website URL. If omitted, `{settings.docs.url}/{book.name}` is
 *      used, e.g. `/docs/framework`
 * @property string $default_version_name The default book
 *      version. If omitted, the last defined version is considered the
 *      default version.
 * If omitted
 * @property Version[] $versions
 */
class Book
{

}