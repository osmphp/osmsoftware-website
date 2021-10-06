<?php

namespace Osm\Docs\Docs;

use Osm\Core\App;
use Osm\Core\Object_;
use Osm\Framework\Db\Db;
use Illuminate\Database\Query;

/**
 * @property Db $db
 * @property Query\Builder $query
 */
class Pages extends Object_
{
    protected function get_db(): Db {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->db;
    }

    protected function get_query(): Query\Builder {
        return $this->db->table('docs');
    }

    /**
     * @param Page $page
     */
    public function childrenOf(Page $page): static {
        $this->query
            ->where('book', $page->version->book->name)
            ->where('version', $page->version->name)
            ->where('parent_url', $page->child_url)
            ->orderBy('sort_order');

        return $this;
    }

    /**
     * @return Page[]
     */
    public function get(): array {
        return $this->query->get(Page::KEY_DB_COLUMNS)
            ->map(fn(\stdClass $item) => Page::fromDb($item))
            ->toArray();
    }
}