<?php

declare(strict_types=1);

namespace My\Posts;

use Osm\Core\Exceptions\NotImplemented;
use Osm\Core\Object_;

/**
 * @property Filter $filter
 * @property int|float|string $value
 * @property int $count
 *
 * @property string $title
 * @property string $title_html
 * @property string|Url $add_url
 * @property string|Url $remove_url
 * @property bool $applied
 * @property bool $visible
 */
class FilterItem extends Object_
{
    protected function get_title_html(): string {
        return htmlspecialchars($this->title);
    }

    protected function get_title(): string {
        throw new NotImplemented($this);
    }

    protected function get_add_url(): string|Url {
        throw new NotImplemented($this);
    }

    protected function get_remove_url(): string|Url {
        throw new NotImplemented($this);
    }

    protected function get_applied(): bool {
        throw new NotImplemented($this);
    }

    protected function get_visible(): bool {
        return true;
    }
}