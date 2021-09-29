<?php

namespace Osm\Docs\Docs;

use Osm\Core\App;
use Osm\Data\Markdown\File;

/**
 * @property Version $version
 */
class Page extends File
{
    public const KEY_DB_COLUMNS = ['id', 'book', 'version', 'path'];

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
}