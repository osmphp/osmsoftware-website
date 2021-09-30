<?php

namespace Osm\Docs\Docs;

use Osm\Core\App;
use Osm\Core\Object_;
use Osm\Core\Attributes\Serialized;
use Osm\Framework\Settings\Hints\Settings;

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
 * @property string $url #[Serialized] The base URL of the book, relative
 *      to the website URL. If omitted, `{settings.docs.url}/{book.name}` is
 *      used, e.g. `/docs/framework`
 * @property string $default_version_name #[Serialized] The default book
 *      version. If omitted, the last defined version is considered the
 *      default version.
 * @property Version[] $versions #[Serialized]
 *
 * @property \stdClass|Settings $settings
 * @property Version $default_version
 */
class Book extends Object_
{
    protected function get_url(): string {
        return "{$this->settings->docs->url}/{$this->name}";
    }

    protected function get_settings(): \stdClass|Settings {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->settings;
    }

    protected function get_default_version_name(): string {
        $names = array_keys($this->versions);
        return end($names);
    }

    protected function get_default_version(): Version {
        return $this->versions[$this->default_version_name];
    }
}