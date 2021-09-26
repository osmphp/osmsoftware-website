<?php

declare(strict_types=1);

namespace Osm\Blog\Posts;

use Osm\Core\Exceptions\NotImplemented;
use Osm\Core\Object_;
use Osm\Framework\Search\Query;
use function Osm\__;

/**
 * @property string $name
 * @property Posts $collection
 *
 * @property array $http_query
 * @property ?string $unparsed_value
 * @property AppliedFilter[] $applied_filters
 * @property bool $require_facet_query
 * @property string $component Blade component to render this filter with
 * @property bool $visible
 * @property string $title
 * @property string $title_html
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

    protected function get_title(): string {
        throw new NotImplemented($this);
    }

    protected function get_title_html(): string {
        return htmlspecialchars($this->title);
    }

    protected function get_visible(): bool {
        return $this->component !== null;
    }

    protected function get_items(): array {
        throw new NotImplemented($this);
    }
}