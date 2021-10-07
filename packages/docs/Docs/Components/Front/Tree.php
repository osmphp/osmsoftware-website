<?php

namespace Osm\Docs\Docs\Components\Front;

use Osm\Docs\Docs\Page;
use Osm\Docs\Docs\Version;
use Osm\Framework\Blade\Component;

/**
 * @property Page[] $top_level_pages
 */
class Tree extends Component
{
    public string $__template = 'docs::components.tree';

    public function __construct(public Version $version)
    {
    }

    protected function get_top_level_pages(): array {
        if (!($page = $this->version->index_page)) {
            return [];
        }

        if (empty($page->children)) {
            $this->version->index_page->fetchChildren();
        }

        return $page->children;
    }
}