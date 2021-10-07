<?php

namespace Osm\Docs\Docs\Placeholder;

use Osm\Core\Attributes\Name;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Data\Markdown\Attributes\In_;
use Osm\Data\Markdown\File;
use Osm\Data\Markdown\Placeholder;
use Osm\Docs\Docs\Page;
use function Osm\__;

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
        if (empty($file->children)) {
            $file->fetchChildren();
        }

        return $this->renderChildren($file);
    }

    protected function renderChildren(Page $page, int $headingLevel = 2): string {
        $markdown = '';

        foreach ($page->children as $childPage) {
            $markdown .= str_repeat('#', $headingLevel) .
                " {$childPage->title}\n\n{$childPage->abstract}\n\n";

            if (empty($childPage->children)) {
                $markdown .= "[" . __("Read more"). "]($$childPage->absolute_url)\n\n";
            }
            else {
                $markdown .= $this->renderChildren($childPage,
                    $headingLevel + 1);
            }
        }

        return $markdown;
    }
}