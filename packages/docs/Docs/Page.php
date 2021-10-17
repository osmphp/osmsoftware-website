<?php

namespace Osm\Docs\Docs;

use Osm\Core\App;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Core\Exceptions\NotSupported;
use Osm\Data\Markdown\File;
use Osm\Framework\Db\Db;
use function Osm\__;

/**
 * @property Version $version
 * @property string $url
 * @property ?int $sort_order
 * @property Page[] $children
 * @property int $level
 * @property Db $db
 * @property string $absolute_url
 * @property Page[] $parents
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
            }
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

        return '/' . implode('/', $urls) . '.html';
    }

    protected function get_children(): array {
        return [];
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

    protected function get_level(): int {
        return $this->url === '/index.html'
            ?  0
            : count(explode('/', $this->url)) - 1;
    }

    public function fetchChildren(int $depth = null): void {
        $query = $this->db->table('docs')
            ->select(static::KEY_DB_COLUMNS)
            ->where('book', $this->version->book->name)
            ->where('version', $this->version->name)
            ->orderBy('url');

        if ($this->url === '/index.html') {
            $query
                ->where('url', 'like', '/%')
                ->where('level', '>', 0);
        }
        else {
            $path = mb_substr($this->url, 0,
                mb_strlen($this->url) - mb_strlen('.html'));

            $query->where('url', 'like', "{$path}/%");
        }

        if ($depth !== null) {
            $query->where('level', '<=',
                $this->level + $depth);
        }

        $this->children = $this->processChildren($query
            ->get()
            ->map(fn(\stdClass $item) => static::fromDb($item))
            ->toArray());
    }

    protected function get_db(): Db {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->db;
    }

    /**
     * @param Page[] $pages
     * @param int $offset
     * @return Page[]
     */
    protected function processChildren(array $pages, int &$offset = 0): array {
        $children = [];
        $lastChild = null;

        while ($offset < count($pages)) {
            if (!$pages[$offset]) {
                $offset++;
                continue;
            }

            if ($pages[$offset]->level <= $this->level) {
                // next sibling
                break;
            }

            if ($pages[$offset]->level === $this->level + 1) {
                // direct child
                $children[$pages[$offset]->path] = $lastChild = $pages[$offset];
                $offset++;
                continue;
            }

            if ($lastChild && $pages[$offset]->level === $this->level + 2) {
                // grand child
                $lastChild->children = $lastChild->processChildren($pages, $offset);
            }
        }

        uasort($children, fn(Page $a, Page $b) =>
            ($a->sort_order ?? PHP_INT_MAX )<=> ($b->sort_order ?? PHP_INT_MAX));

        return $children;
    }

    protected function get_absolute_url(): string {
        return "{$this->version->absolute_url}{$this->url}";
    }

    protected function get_parents(): array {
        $parents = [];
        $urls = explode('/', $this->url);

        if (count($urls) < 3) {
            return $parents;
        }

        for ($i = 1; $i < count($urls) - 1; $i++) {
            $item = $this->db->table('docs')
                ->select(static::KEY_DB_COLUMNS)
                ->where('book', $this->version->book->name)
                ->where('version', $this->version->name)
                ->where('url', implode('/',
                    array_slice($urls, 0, $i + 1)) . '.html')
                ->first();

            if ($item) {
                $parents[] = static::fromDb($item);
            }
        }

        return $parents;
    }

    protected function generateRelativeUrl(string $absolutePath): ?string {
        return static::new([
            'version' => $this->version,
            'path' => mb_substr($absolutePath, mb_strlen("{$this->root_path}/")),
        ])->absolute_url;
    }
}