<?php

namespace Osm\Docs\Docs\Sources;

use Osm\Core\Attributes\Name;
use Osm\Data\Indexing\Source;

/**
 * The indexing source that contains all documentation pages, in all books
 * and versions that are specified in the application settings, `docs` section.
 *
 * `pattern` property, if not empty, should be in
 * `{book_name}/{version_name}/{path}` format, where `path` is a directory,
 * or a file name within that version. If it's specified, only specified
 * directory or file is re-indexed.
 *
 * If `pattern` is not specified, then all files, in all books and versions,
 * that are modified since last indexing, are re-indexed.
 */
#[Name('docs')]
class Docs extends Source
{

}