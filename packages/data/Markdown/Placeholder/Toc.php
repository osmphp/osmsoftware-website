<?php

namespace Osm\Data\Markdown\Placeholder;

use Osm\Core\Attributes\Name;
use Osm\Data\Markdown\File;
use Osm\Data\Markdown\Placeholder;
use Osm\Data\Markdown\Attributes\Context;

/**
 * @property File $file #[Context]
 */
#[Name('toc')]
class Toc extends Placeholder
{
    public function render(): ?string
    {
        $markdown = '';

        foreach ($this->file->toc as $urlKey => $tocEntry) {
            $markdown .= str_repeat(' ', ($tocEntry->depth - 2) * 4)
                . "* [" . $tocEntry->title . "](#{$urlKey})\n";
        }
        return "{$markdown}\n";
    }
}