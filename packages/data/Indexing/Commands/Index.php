<?php

namespace Osm\Data\Indexing\Commands;

use Osm\Data\Indexing\Runner;
use Osm\Framework\Console\Command;
use Osm\Framework\Console\Attributes\Argument;
use Osm\Framework\Console\Attributes\Option;

/**
 * @property string[] $source_pattern #[Argument]
 * @property bool $rebuild #[Option(shortcut: 'f')] Rebuild the index from scratch
 * @property string[] $patterns `source_pattern` converted from
 *      `{source}[/{scope}]` parameters into the form of
 *      `['{source}' => '{scope}|null']`
 */
class Index extends Command
{
    public string $name = 'index';

    public function run(): void
    {
        Runner::new([
            'rebuild' => $this->rebuild,
            'patterns' => $this->patterns,
            'output' => $this->output,
        ])->run();
    }

    protected function get_patterns(): array {
        $patterns = [];

        if (empty($this->source_pattern)) {
            return $patterns;
        }

        foreach ($this->source_pattern as $pattern) {
            $name = ($pos = mb_strpos($pattern, '/')) !== false
                ? mb_substr($pattern, 0, $pos)
                : $pattern;

            $patterns[$name] = $pos !== false
                ? mb_substr($pattern, $pos + 1)
                : null;
        }

        return $patterns;
    }
}