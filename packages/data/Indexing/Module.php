<?php

namespace Osm\Data\Indexing;

use Osm\Core\App;
use Osm\Core\BaseModule;
use Osm\Framework\Cache\Attributes\Cached;
use Osm\Framework\Cache\Descendants;

/**
 * @property Source[] $unsorted_sources
 * @property Source[] $sources #[Cached('indexing_sources')]
 * @property Descendants $descendants
 */
class Module extends BaseModule
{
    public static array $requires = [
        \Osm\Framework\Cache\Module::class,
        \Osm\Framework\Console\Module::class,
        \Osm\Framework\Translations\Module::class,
    ];

    protected function get_unsorted_sources(): array {
        $classNames = $this->descendants->byName(Source::class);
        $sources = [];

        foreach ($classNames as $name => $className) {
            $new = "{$className}::new";
            $sources[$name] = $new(['name' => $name]);
        }

        return $sources;
    }

    protected function get_sources(): array {
        $sources = $this->unsorted_sources;

        uasort($sources, function (Source $a, Source $b) {
            if (in_array($b->name, $a->target_names)) {
                return -1;
            }

            if (in_array($a->name, $b->target_names)) {
                return 1;
            }

            return 0;
        });

        return $sources;
    }

    protected function get_descendants(): Descendants {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->descendants;
    }
}