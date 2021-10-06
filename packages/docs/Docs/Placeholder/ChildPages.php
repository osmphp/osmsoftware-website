<?php

namespace Osm\Docs\Docs\Placeholder;

use Osm\Core\Attributes\Name;
use Osm\Data\Markdown\Attributes\In_;
use Osm\Data\Markdown\File;
use Osm\Data\Markdown\Placeholder;
use Osm\Docs\Docs\Page;

#[Name('child_pages'), In_(Page::class)]
class ChildPages extends Placeholder
{
    public bool $starts_on_new_line = true;

    /**
     * @param Page $file
     * @return ?string
     */
    public function render(File $file): ?string
    {
        $markdown = '';

        foreach ($file->child_pages as $childPage) {
            $markdown .= "* [" . $childPage->title . "]" .
                "({$childPage->relative_child_url})\n";
        }
        return "{$markdown}\n";
    }
}