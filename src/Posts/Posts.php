<?php

declare(strict_types=1);

namespace My\Posts;

use Illuminate\Support\Collection;
use Osm\Core\App;
use Osm\Core\Object_;
use Osm\Framework\Db\Db;
use Osm\Framework\Search\Query;
use Osm\Framework\Search\Result;

/**
 * @property Query $search_query
 * @property Result $search_result
 * @property Db $db
 * @property Collection $db_records
 * @property int $count
 * @property Post[] $parsers
 * @property Post[] $items
 */
class Posts extends Object_
{
    protected function get_search_result() {
        return $this->search_query->get();
    }

    protected function get_count(): int {
        return $this->search_result->count;
    }

    protected function get_db(): Db {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->db;
    }

    protected function get_db_records(): Collection {
        return $this->db->table('posts')
            ->whereIn('id', $this->search_result->ids)
            ->get(['id', 'path']);
    }

    protected function get_parsers(): Collection {
        return $this->db_records
            ->keyBy('id')
            ->map(fn($post) => Post::new(['path' => $post->path]))
            ->filter(fn(Post $file) => $file->exists);
    }

    protected function get_items(): array {
        $items = [];

        foreach ($this->search_result->ids as $id) {
            $items[$id] = $this->parsers[$id] ?? null;
        }

        return $items;
    }
}