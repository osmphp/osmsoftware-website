<?php

namespace Osm\Data\Indexing\Commands;

use Osm\Core\App;
use Osm\Core\BaseModule;
use Osm\Data\Indexing\Module;
use Osm\Data\Indexing\Source;
use Osm\Framework\Console\Command;
use Osm\Framework\Console\Attributes\Argument;
use Osm\Framework\Console\Attributes\Option;

/**
 * @property string[] $source_pattern #[Argument]
 * @property bool $rebuild #[Option(shortcut: 'f')] Rebuild the index from scratch
 * @property Module $module
 * @property Source[] $sources
 */
class Index extends Command
{
    public string $name = 'index';

    public function run(): void
    {
        foreach ($this->sources as $source) {
            $this->output->writeln($source->name);
            foreach ($source->indexer_class_names as $indexerClassName) {
                $this->output->writeln("    {$indexerClassName}");
            }
        }
    }

    protected function get_module(): BaseModule {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->modules[Module::class];
    }

    protected function get_sources(): array {
        return $this->module->sources;
    }
}