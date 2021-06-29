<?php

declare(strict_types=1);

namespace My\Posts\Commands;

use My\Markdown\Exceptions\InvalidPath;
use My\Posts\Post;
use Osm\Core\App;
use Osm\Framework\Console\Command;
use Osm\Framework\Console\Exceptions\ConsoleError;
use function Osm\__;

/**
 * @property string $root_path
 * @property bool $found
 */
class CheckLinks extends Command
{
    public string $name = 'check:links';

    public function run(): void {
        $this->scanPath();

        if ($this->found) {
            // exit with error code
            throw new ConsoleError('', 1);
        }
    }

    protected function get_root_path(): string {
        global $osm_app; /* @var App $osm_app */

        return "{$osm_app->paths->data}/posts";
    }

    protected function scanPath(string $path = null): void {
        $absolutePath = $path
            ? "{$this->root_path}/{$path}"
            : $this->root_path;

        if (is_dir($absolutePath)) {
            foreach (new \DirectoryIterator($absolutePath) as $fileInfo) {
                /* @var \SplFileInfo $fileInfo */
                if ($fileInfo->isDot()) {
                    continue;
                }

                $this->scanPath($path
                    ? "{$path}/{$fileInfo->getFilename()}"
                    : $fileInfo->getFilename());
            }
            return;
        }

        if (is_file($absolutePath)) {
            if (str_ends_with($absolutePath, '.md')) {
                $this->scanFile($path);
            }
            return;
        }

        throw new InvalidPath(__(
            "':path' is and a not valid blog post file path",
            ['path' => $path]));
    }

    protected function scanFile(string $path): void {
        $post = Post::new(['path' => $path]);

        if (empty($post->broken_links)) {
            return;
        }

        $this->output->writeln(__("':file_path':", [
            'file_path' => $path,
        ]));

        foreach ($post->broken_links as $brokenLink) {
            $this->output->writeln(__("  ':path' not found", [
                'path' => $brokenLink,
            ]));
            $this->found = true;
        }
    }

}