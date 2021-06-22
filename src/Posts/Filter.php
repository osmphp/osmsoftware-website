<?php

declare(strict_types=1);

namespace My\Posts;

use Osm\Core\Exceptions\NotImplemented;
use Osm\Core\Object_;
use Osm\Framework\Search\Query;

/**
 * @property string $name
 * @property Posts $collection
 *
 * @property array $http_query
 * @property ?string $unparsed_value
 * @property AppliedFilter[] $applied_filters
 * @property bool $require_facet_query
 */
class Filter extends Object_
{
    public function apply(Query $query): void {
        throw new NotImplemented($this);
    }

    public function requestFacets(Query $query): void {
        throw new NotImplemented($this);
    }

    protected function get_http_query(): array {
        return $this->collection->http_query;
    }

    protected function get_unparsed_value(): ?string {
        return $this->http_query[$this->name] ?? null;
    }

    /**
     * @param AppliedFilter[] $appliedFilters
     * @return string
     */
    public function url(array $appliedFilters): string {
        throw new NotImplemented($this);
    }
}