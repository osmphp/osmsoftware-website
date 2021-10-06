<?php

namespace Osm\Docs\Docs;

use Osm\Core\App;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Core\Exceptions\NotSupported;
use Osm\Data\Markdown\File;
use function Osm\__;

/**
 * @property Version $version
 * @property string $url
 * @property ?int $sort_order
 * @property Page[] $child_pages
 * @property ?string $parent_url
 * @property string $child_url
 * @property string $relative_child_url
 */
class Page extends File
{
    public const KEY_DB_COLUMNS = ['id', 'book', 'version', 'path'];

    public const PATH_PATTERN = '|^(?:(?<sort_order>[0-9]+)-)?(?<url_key>.+)$|u';
    public const NAME_PATTERN = '|^(?:(?<sort_order>[0-9]+)-)?(?<url_key>.+)\.md$|u';

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
                else {
                    throw new NotSupported(__(
                        "Documentation page path segment ':path' should follow '[01-]foo' convention.",
                        ['path' => $path]));
                }

                $urls[] = $path;
            }
        }

        return implode('/', $urls) . '.html';
    }

    protected function get_child_pages(): array {
        return Pages::new()->childrenOf($this)->get();
    }

    protected function get_parent_url(): ?string {
        if ($this->path === 'index.md') {
            return null;
        }

        return ($pos = mb_strrpos($this->url, '/')) === false
            ? ''
            : mb_substr($this->url, 0, $pos);
    }

    protected function get_child_url(): string {
        return $this->path === 'index.md'
            ? ''
            : mb_substr($this->url, 0, mb_strlen($this->url) - mb_strlen('.html'));
    }

    protected function get_sort_order(): ?int {
        $path = basename($this->path);

        if (preg_match(static::NAME_PATTERN, $path, $match)) {
            return !empty($match['sort_order'])
                ? (int)$match['sort_order']
                : null;
        }
        else {
            throw new NotSupported(__(
                "Documentation page filename ':path' should follow '[01-]foo.md' convention.",
                ['path' => $path]));
        }
    }

    protected function get_relative_child_url(): string {
        if (!$this->parent_url) {
            return $this->url;
        }

        $parentUrl = ($pos = mb_strrpos($this->parent_url, '/')) !== false
            ? mb_substr($this->parent_url, $pos)
            : $this->parent_url;

        return "$parentUrl/" . mb_substr($this->url, mb_strrpos($this->url, '/'));
    }
}