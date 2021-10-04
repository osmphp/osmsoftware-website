<?php

namespace Osm\Docs\Docs;

use Osm\Core\App;
use Osm\Data\Markdown\File;

/**
 * @property Version $version
 * @property string $url
 */
class Page extends File
{
    public const KEY_DB_COLUMNS = ['id', 'book', 'version', 'path'];

    public const PATH_PATTERN = '|^(?:(?<sort_order>[0-9]{2})-)?(?<url_key>.+)$|u';

    public static function fromDb(\stdClass $item): ?static {
        global $osm_app; /* @var App $osm_app */

        /* @var Module $module */
        $module = $osm_app->modules[Module::class];

        if (!($version = $module->books[$item->book]
            ?->versions[$item->version] ?? null))
        {
            return null;
        }

        $page = Page::new(['version' => $version, 'path' => $item->path]);

        return $page->exists ? $page : null;
    }

    protected function get_root_path(): string {
        return $this->version->root_path;
    }

    protected function get_url(): string {
        $urls = [];

        $paths = explode('/', $this->path);
        foreach ($paths as $i => $path) {
            if ($i == count($paths) - 1) {
                $path = mb_substr($path, 0, mb_strrpos($path, '.md'));

                if (preg_match(static::PATH_PATTERN, $path, $match)) {
                    $path = $match['url_key'];
                }

                $urls[] = $path;
            }
        }

        return implode('/', $urls) . '.html';
    }
}