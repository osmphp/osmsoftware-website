<?php

declare(strict_types=1);

namespace My\Posts;

use Osm\Core\Object_;

/**
 * @property Filter $filter
 * @property string $title
 * @property string $title_html
 * @property string $value
 * @property string $value_html
 * @property string|Url $clear_url
 * @property Posts $collection
 */
class AppliedFilter extends Object_
{
    protected function get_title_html(): string {
        return htmlspecialchars($this->title);
    }

    protected function get_value_html(): string {
        return htmlspecialchars($this->value);
    }

    protected function get_collection(): Posts {
        return $this->filter->collection;
    }
}