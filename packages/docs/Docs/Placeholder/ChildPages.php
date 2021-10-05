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
    public function render(File $file): ?string
    {
        return 'Hello, world!';
    }
}