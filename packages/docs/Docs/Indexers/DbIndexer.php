<?php

namespace Osm\Docs\Docs\Indexers;

use Carbon\Carbon;
use Osm\Core\App;
use Osm\Core\BaseModule;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Data\Indexing\Indexer;
use Osm\Data\Markdown\Exceptions\InvalidPath;
use Osm\Docs\Docs\Book;
use Osm\Docs\Docs\Module;
use Osm\Docs\Docs\Page;
use Osm\Docs\Docs\Sources\DbDocs;
use Osm\Docs\Docs\Sources\Docs;
use Osm\Docs\Docs\Version;
use Osm\Framework\Cache\Cache;
use Osm\Framework\Db\Db;
use Osm\Framework\Settings\Hints\Settings;
use function Osm\__;

/**
 * @property Docs $docs
 * @property DbDocs $target
 *
 * @property Db $db
 * @property Cache $cache
 * @property Module $module
 * @property Book[] $books
 * @property \stdClass|Settings $settings
 * @property Carbon $last_record_modified_at
 */
class DbIndexer extends Indexer
{
    public function run(): void {
        $this->cache->deleteItem('docs_books');

        if ($this->rebuild()) {
            $this->clear();
        }

        $this->index();
        $this->retire();
    }

    protected function get_db(): Db {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->db;
    }

    protected function get_cache(): Cache {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->cache;
    }

    protected function clear(): void {
        $this->db->table('docs')->delete();

        $this->target->rebuild = true;
    }

    protected function index(): void {
        foreach ($this->books as $book) {
            $this->indexBook($book);
        }
    }

    protected function indexBook(Book $book): void {
        foreach ($book->versions as $version) {
            $this->indexVersion($version);
        }
    }

    protected function indexVersion(Version $version): void  {
        $this->indexPath($version);
    }

    protected function indexPath(Version $version, ?string $path = null): void {
        $absolutePath = $path
            ? "{$version->root_path}/{$path}"
            : $version->root_path;

        if (is_dir($absolutePath)) {
            foreach (new \DirectoryIterator($absolutePath) as $fileInfo) {
                /* @var \SplFileInfo $fileInfo */
                if ($fileInfo->isDot()) {
                    continue;
                }

                $this->indexPath($version, $path
                    ? "{$path}/{$fileInfo->getFilename()}"
                    : $fileInfo->getFilename());
            }
            return;
        }

        if (is_file($absolutePath)) {
            if (str_ends_with($absolutePath, '.md')) {
                $this->indexPage($version, $path);
            }
            return;
        }

        throw new InvalidPath(__(
            "':path' is and a not valid documentation path",
            ['path' => $absolutePath]));
    }

    protected function get_module(): BaseModule {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->modules[Module::class];
    }

    protected function get_books(): array {
        return $this->module->books;
    }

    protected function indexPage(Version $version, ?string $path): void {
        $page = Page::new(['version' => $version, 'path' => $path]);

        if ($this->skip($page)) {
            return;
        }

        if ($id = $this->find($page)) {
            $this->update($id, $page);
        }
        else {
            $this->insert($page);
        }
    }

    protected function find(Page $page): ?int {
        return $this->db->table('docs')
            ->where('book', $page->version->book->name)
            ->where('version', $page->version->name)
            ->where('path', $page->path)
            ->value('id');
    }

    protected function update(int $id, Page $page): void {
        $this->db->table('docs')
            ->where('id', $id)
            ->update([
                'modified_at' => $page->modified_at,
                'deleted_at' => null,
            ]);

        $this->idSaved($id);
    }

    protected function insert(Page $page): int {
        return $this->idSaved($this->db->table('docs')->insertGetId([
            'book' => $page->version->book->name,
            'version' => $page->version->name,
            'path' => $page->path,
            'url' => $page->url,
            'modified_at' => $page->modified_at,
        ]));
    }

    protected function retire(): void {
        $query = $this->db->table('docs')
            ->whereNull('deleted_at');

        foreach ($query->get(Page::KEY_DB_COLUMNS) as $item) {
            if (!Page::fromDb($item)) {
                $this->retirePage($item->id);
            }
        }
    }

    protected function retirePage($id): void {
        $this->db->table('docs')
            ->where('id', $id)
            ->update(['deleted_at' => Carbon::now()]);

        $this->idDeleted($id);
    }

    protected function skip(Page $page): bool {
        if ($this->rebuild()) {
            return false;
        }

        if (!isset($this->settings->docs->index_modified)) {
            return false;
        }

        return $page->modified_at->lte($this->last_record_modified_at);
    }

    protected function get_settings(): \stdClass|Settings {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->settings;
    }

    protected function get_last_record_modified_at(): ?Carbon {
        $modifiedAt = $this->db->table('docs')
            ->max('modified_at');

        return $modifiedAt
            ? Carbon::parse($modifiedAt)
            : null;
    }
}