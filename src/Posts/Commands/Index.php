<?php

declare(strict_types=1);

namespace My\Posts\Commands;

use My\Posts\Indexer;
use Osm\Framework\Console\Command;

class Index extends Command
{
    public string $name = 'index';

    public function run(): void {
        Indexer::new()->run();
    }
}