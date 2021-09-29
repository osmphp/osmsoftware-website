<?php

namespace Osm\Docs\Docs\Hints\Settings;

/**
 * @property string $path Absolute path to the local repository files
 * @property ?string $repo Remote repository URL. If omitted, $path is
 *      considered not a cloned Git repo, but a local directory where
 *      the book pages are edited locally
 * @property ?string $branch Remote repository branch.
 *      If omitted, $path is considered not a cloned Git repo, but
 *      a local directory where the book pages are edited locally
 * @property ?string $dir A subdirectory inside $path where the book pages
 *      are stored. If omitted, it is assumed that $path stores the book pages
 */
class Version
{

}