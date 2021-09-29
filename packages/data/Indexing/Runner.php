<?php

namespace Osm\Data\Indexing;

use Osm\Core\App;
use Osm\Core\Attributes\Name;
use Osm\Core\Class_;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Core\Object_;
use Osm\Data\Indexing\Exceptions\InvalidSource;
use Osm\Framework\Cache\Descendants;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use function Osm\__;
use function Osm\sort_by_dependency;

/**
 * Runs all the indexers that have some, or all, data invalidated in their
 * sources.
 *
 * @property bool $rebuild Specifies how the invalidated data is processed.
 *      If `true`, indexers should dump the existing data, and compute
 *      it all anew. Otherwise, indexers should process only invalidated data,
 *      which is usually faster. The default is incremental processing.
 * @property string[] $patterns The unparsed scope of invalidated data
 *      specified in the `osm index {source}[/{scope}]` parameters,
 *      in the form of ['{source}' => '{scope}|null']. The exact meaning of
 *      every pattern varies across different sources.
 *      It's ignored in the `rebuild` mode.
 * @property OutputInterface $output A stream the runner writes output to.
 *      If omitted, defaults to a string buffer.
 *
 * @property Indexer[] $unsorted_indexers All the indexers defined in the
 *      application
 * @property Indexer[] $indexers All the indexers defined in the application,
 *      ordered by dependency.
 * @property Source[] $sources All the sources defined in the application.
 * @property Descendants $descendants
 * @property Class_[] $indexer_classes
 * @property string[] $source_class_names
 */
class Runner extends Object_
{
    public function run(): void {
        $this->validatePatterns();

        foreach ($this->indexers as $indexer) {
            if ($indexer->shouldRun()) {
                $indexer->run();
                $indexer->report();
            }
        }
    }

    protected function get_patterns(): array {
        return [];
    }

    protected function get_output(): OutputInterface {
        return new BufferedOutput();
    }

    protected function get_unsorted_indexers(): array {
        global $osm_app; /* @var App $osm_app */

        $indexers = [];

        foreach ($this->indexer_classes as $class) {
            $new = "{$class->name}::new";

            $data = ['runner' => $this];
            $sources = [];

            foreach ($class->properties as $property) {
                if ($property->name === 'sources') {
                    continue;
                }

                if (!$property->type) {
                    continue;
                }

                if (!is_a($property->type, Source::class, true)) {
                    continue;
                }

                $sourceName = $osm_app->classes[$property->type]
                        ->attributes[Name::class]->name;

                if ($property->name != 'target' &&
                    $sourceName != $property->name)
                {
                    throw new InvalidSource(__(
                        "Source property ':indexer:::source' should be named ':correct_source'", [
                            'source' => $property->name,
                            'indexer' => $class->name,
                            'correct_source' => $sourceName,
                        ]));
                }

                if (!isset($this->sources[$sourceName])) {
                    throw new InvalidSource(__(
                        "Source ':source', requested by ':indexer' indexer, is not defined", [
                            'source' => $sourceName,
                            'indexer' => $class->name,
                        ]));
                }

                $data[$property->name] = $this->sources[$sourceName];
                if ($property->name != 'target') {
                    $sources[$property->name] = $this->sources[$sourceName];
                }
            }

            $data['sources'] = $sources;
            $indexers[$class->name] = $new($data);
        }

        return $indexers;
    }

    protected function get_indexers(): array {
        return sort_by_dependency($this->unsorted_indexers,
            __("Indexing sources"),
            fn($positions) =>
                fn(Indexer $a, Indexer $b) =>
                    $positions[$a->__class->name] <=> $positions[$b->__class->name]
        );
    }

    protected function get_descendants(): Descendants {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->descendants;
    }

    protected function get_indexer_classes(): array {
        return $this->descendants->classes(Indexer::class);
    }

    protected function get_sources(): array {
        $sources = [];

        foreach ($this->source_class_names as $name => $className) {
            $new = "{$className}::new";
            $sources[$name] = $new([
                'name' => $name,
                'rebuild' => $this->rebuild,
                'pattern' => $this->patterns[$name] ?? null,
                'invalidated' => empty($this->patterns) ||
                    array_key_exists($name, $this->patterns),
            ]);
        }

        return $sources;
    }

    protected function get_source_class_names(): array {
        return $this->descendants->byName(Source::class);
    }

    protected function validatePatterns(): void {
        foreach ($this->patterns as $name => $pattern) {
            if (!isset($this->source_class_names[$name])) {
                throw new InvalidSource(__(
                    "Source ':source' is not defined",
                    ['source' => $name]));
            }
        }
    }
}