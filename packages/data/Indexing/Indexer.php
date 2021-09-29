<?php

namespace Osm\Data\Indexing;

use Osm\Core\Exceptions\NotImplemented;
use Osm\Core\Object_;
use Symfony\Component\Console\Output\OutputInterface;
use function Osm\__;

/**
 * Computes data in the `target` for provided `sources`, and maintains
 * the scope of invalidated data in `target`, as it may be a source for another
 * indexer.
 *
 * @property Source[] $sources Sources of this indexing operation. Contains
 *      all properties of the `Source` class, except `target` property.
 * @property Source $target The target of this indexing operation.
 * @property Runner $runner The index operation runner
 *
 * @property OutputInterface $output A stream the indexer writes output to.
 * @property string[] $after Indexer class names that should
 *      run prior this indexer
 */
class Indexer extends Object_
{
    protected int $saved = 0;
    protected int $deleted = 0;

    public function run(): void {
        throw new NotImplemented($this);
    }

    public function report(): void {
        $this->output->writeln(__(
            ":target: :saved record(s) inserted/updated, :deleted record(s) deleted.", [
                'target' => $this->target->name,
                'saved' => $this->saved,
                'deleted' => $this->deleted,
            ]));
    }

    protected function get_output(): OutputInterface {
        return $this->runner->output;
    }

    protected function get_after(): array {
        return array_keys(array_filter($this->runner->unsorted_indexers,
            fn(Indexer $indexer) =>
                isset($this->sources[$indexer->target->name])
        ));
    }

    public function shouldRun(): bool {
        foreach ($this->sources as $source) {
            if ($source->rebuild || $source->invalidated) {
                return true;
            }
        }

        return false;
    }

    public function rebuild(): bool {
        foreach ($this->sources as $source) {
            if ($source->rebuild) {
                return true;
            }
        }

        return false;
    }

    protected function idDeleted(int $id): int {
        if (!$this->target->rebuild) {
            $this->target->deleted_ids[$id] = true;
            $this->target->invalidated = true;
        }
        $this->deleted++;

        return $id;
    }

    protected function idSaved(int $id): int {
        if (!$this->target->rebuild) {
            $this->target->saved_ids[$id] = true;
            $this->target->invalidated = true;
        }
        $this->saved++;

        return $id;
    }
}