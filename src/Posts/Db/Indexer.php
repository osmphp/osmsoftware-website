<?php

declare(strict_types=1);

namespace My\Posts\Db;

use My\Posts\Exceptions\InvalidPath;
use My\Posts\MarkdownParser;
use Osm\Core\App;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Core\Object_;
use Osm\Framework\Db\Db;
use function Osm\__;

/**
 * @property ?string $path
 * @property string $root_path
 * @property Db $db
 */
class Indexer extends Object_
{
    public function run(): void {
        $this->indexPath($this->path);
        $this->markDeletedFiles();
    }

    protected function indexPath(?string $path): void {
        $absolutePath = $path
            ? "{$this->root_path}/{$path}"
            : $this->root_path;

        if (is_dir($absolutePath)) {
            foreach (new \DirectoryIterator($absolutePath) as $fileInfo) {
                /* @var \SplFileInfo $fileInfo */
                if ($fileInfo->isDot()) {
                    continue;
                }

                $this->indexPath($path
                    ? "{$path}/{$fileInfo->getFilename()}"
                    : $fileInfo->getFilename());
            }
            return;
        }

        if (is_file($absolutePath)) {
            if (str_ends_with($absolutePath, '.md')) {
                $this->indexFile($path);
            }
            return;
        }

        throw new InvalidPath(__(
            "':path' is and a not valid blog post file path",
            ['path' => $this->path]));
    }

    protected function get_root_path(): string {
        global $osm_app; /* @var App $osm_app */

        return "{$osm_app->paths->data}/posts";
    }

    protected function indexFile(string $path): void {
        if ($this->db->table('posts')
            ->where('path', $path)
            ->exists())
        {
            $this->db->table('posts')->update([
                'deleted' => false,
            ]);
        }

        $this->db->table('posts')->insert([
            'path' => $path,
        ]);
    }

    protected function get_db(): Db {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->db;
    }

    protected function markDeletedFiles() {
        $path = $this->path ?? '';
        if (str_ends_with($path, '.md')) {
            $path = dirname($path);
        }

        $query = $this->db->table('posts');
        if ($path) {
            $query->where('path', 'like', "{$path}/%");
        }

        foreach ($query->pluck('path') as $path) {
            $absolutePath = "{$this->root_path}/{$path}";
            if (!is_file($absolutePath)) {
                $this->db->table('posts')
                    ->where('path', $path)
                    ->update(['deleted' => true]);
            }
        }
    }
}