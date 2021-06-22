<?php

declare(strict_types=1);

namespace My\Posts;

use Osm\Core\Object_;

/**
 * @property string $name
 * @property Posts $collection
 *
 * @property array $http_query
 * @property ?string $unparsed_value
 * @property AppliedFilter[] $applied_filters
 */
class Filter extends Object_
{
    protected function get_http_query(): array {
        return $this->collection->http_query;
    }

    protected function get_unparsed_value(): ?string {
        return $this->http_query[$this->name] ?? null;
    }
}