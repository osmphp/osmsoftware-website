<?php

declare(strict_types=1);

namespace Osm\Blog\Posts\Commands;

use Osm\Blog\Posts\Indexer;
use Osm\Framework\Console\Command;
use Osm\Framework\Console\Attributes\Option;

/**
 * @property bool $rebuild #[Option(shortcut: 'f')] Rebuild the index from scratch
 */
class Index extends Command
{
    public string $name = 'index:blog';

    public function run(): void {
        Indexer::new()->run($this->rebuild);
    }
}