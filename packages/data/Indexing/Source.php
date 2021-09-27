<?php

namespace Osm\Data\Indexing;

use Osm\Core\App;
use Osm\Core\Class_;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Core\Object_;
use Osm\Core\Attributes\Serialized;
use Osm\Data\Indexing\Attributes\Source as SourceAttribute;
use Osm\Data\Indexing\Attributes\Target;

/**
 * @property string $name #[Serialized]
 * @property string[] $indexer_class_names #[Serialized]
 * @property string[] $target_names #[Serialized]
 */
class Source extends Object_
{
    protected function get_indexer_class_names(): array {
        global $osm_app; /* @var App $osm_app */

        $classNames = [];
        $classes = $osm_app->descendants->classes(Indexer::class);

        foreach ($classes as $class) {
            $sourceName = $class->attributes[SourceAttribute::class]
                ?->source_name;

            if ($sourceName === $this->name) {
                $classNames[] = $class->name;
            }
        }

        return $classNames;
    }

    protected function get_target_names(): array {
        $this->scanIndexerTargetNamesRecursively($this, $targetNames);

        return array_values($targetNames);
    }

    protected function scanIndexerTargetNamesRecursively(Source $source,
         array  &$targetNames = null, array  &$processedSources = null): void
    {
        global $osm_app; /* @var App $osm_app */

        /* @var Module $module */
        $module = $osm_app->modules[Module::class];

        if (!$targetNames) {
            $targetNames = [];
        }

        if (!$processedSources) {
            $processedSources = [];
        }

        if (isset($processedSources[$source->name])) {
            throw new NotImplemented($this);
        }
        $processedSources[$source->name] = true;

        foreach ($source->indexer_class_names as $className) {
            if (!($targetName = $osm_app->classes[$className]
                ->attributes[Target::class]?->source_name))
            {
                throw new NotImplemented($this);
            }

            if (!isset($module->unsorted_sources[$targetName])) {
                throw new NotImplemented($this);
            }

            $targetNames[$targetName] = $targetName;

            $this->scanIndexerTargetNamesRecursively(
                $module->unsorted_sources[$targetName],
                $targetNames, $processedSources);
        }
    }
}