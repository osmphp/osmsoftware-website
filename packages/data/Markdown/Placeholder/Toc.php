<?php

namespace Osm\Data\Markdown\Placeholder;

use Osm\Core\Attributes\Name;
use Osm\Data\Markdown\Attributes\In_;
use Osm\Data\Markdown\File;
use Osm\Data\Markdown\Placeholder;

#[Name('toc'), In_(File::class)]
class Toc extends Placeholder
{
    public bool $starts_on_new_line = true;

    public function render(File $file): ?string
    {
        $markdown = '';

        foreach ($file->toc as $urlKey => $tocEntry) {
            $markdown .= str_repeat(' ', ($tocEntry->depth - 2) * 4)
                . "* [" . $tocEntry->title . "](#{$urlKey})\n";
        }
        return "{$markdown}\n";
    }
}