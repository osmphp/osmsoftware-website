<?php

namespace Osm\Docs\Docs\Indexers;

use Osm\Core\App;
use Osm\Core\BaseModule;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Data\Indexing\Indexer;
use Osm\Data\Markdown\Exceptions\InvalidPath;
use Osm\Docs\Docs\Book;
use Osm\Docs\Docs\Module;
use Osm\Docs\Docs\Sources\DbDocs;
use Osm\Docs\Docs\Sources\Docs;
use Osm\Docs\Docs\Version;
use Osm\Framework\Cache\Cache;
use Osm\Framework\Db\Db;
use function Osm\__;

/**
 * @property Docs $docs
 * @property DbDocs $target
 *
 * @property Db $db
 * @property Cache $cache
 * @property Module $module
 * @property Book[] $books
 */
class DbIndexer extends Indexer
{
    public function run(): void {
        $this->cache->deleteItem('docs_books');

        if ($this->rebuild()) {
            $this->clear();
        }

        $this->index();
        $this->softDelete();
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
                $this->indexFile($version, $path);
            }
            return;
        }

        throw new InvalidPath(__(
            "':path' is and a not valid documentation path",
            ['path' => $absolutePath]));
    }

    protected function softDelete(): void {
        throw new NotImplemented($this);
    }

    protected function get_module(): BaseModule {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->modules[Module::class];
    }

    protected function get_books(): array {
        return $this->module->books;
    }

    protected function indexFile(Version $version, ?string $path): void {
        throw new NotImplemented($this);
    }
}