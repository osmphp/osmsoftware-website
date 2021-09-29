<?php

namespace Osm\Docs\Docs\Commands;

use Osm\Core\App;
use Osm\Core\BaseModule;
use Osm\Docs\Docs\Book;
use Osm\Docs\Docs\Exceptions\CommandFailed;
use Osm\Docs\Docs\Module;
use Osm\Docs\Docs\Version;
use Osm\Framework\Console\Command;
use function Osm\__;
use function Osm\make_dir;
use Osm\Framework\Console\Attributes\Argument;

/**
 * @property string[] $book_name #[Argument]
 * @property Module $module
 * @property Book[] $books
 */
class Pull extends Command
{
    public string $name = 'pull:docs';

    public function run(): void {
        foreach ($this->books as $book) {
            $this->pullBook($book);
        }
    }

    protected function get_module(): BaseModule {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->modules[Module::class];
    }

    protected function get_books(): array {
        return $this->module->books;
    }

    protected function pullBook(Book $book): void {
        if (!empty($this->book_name) &&
            !in_array($book->name, $this->book_name))
        {
            return;
        }

        foreach ($book->versions as $version) {
            $this->pullVersion($version);
        }
    }

    protected function pullVersion(Version $version): void {
        if (!$version->repo || !$version->branch) {
            return;
        }

        if (is_dir("{$version->path}/.git")) {
            $this->shell("git pull origin {$version->branch}",
                $version->path);
        }
        else {
            $name = basename($version->path);

            $this->shell(
                "git clone -b {$version->branch} {$version->repo} {$name}",
                make_dir(dirname($version->path)));

        }
    }

    protected function shell(string $command, string $cwd): void {
        $lastCwd = getcwd();
        chdir($cwd);

        try {
            passthru($command, $exitCode);

            if ($exitCode) {
                throw new CommandFailed(__(
                    "':command' failed, error code: ':exit_code'",
                    ['command' => $command, 'exit_code' => $exitCode]));
            }
        }
        finally {
            chdir($lastCwd);
        }
    }

}