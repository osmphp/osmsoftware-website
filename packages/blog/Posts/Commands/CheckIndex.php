<?php

declare(strict_types=1);

namespace Osm\Blog\Posts\Commands;

use Osm\Blog\Posts\Posts;
use Osm\Core\App;
use Osm\Framework\Console\Command;
use Osm\Framework\Console\Exceptions\ConsoleError;
use Osm\Framework\Db\Db;
use Osm\Framework\Search\Search;
use function Osm\__;

/**
 * @property Db $db
 * @property Search $search
 */
class CheckIndex extends Command
{
    public string $name = 'check:blog-index';

    public function run(): void {
        $searchIds = $this->search->index('posts')
            ->ids();

        $dbIds = $this->db->table('posts')
            ->whereIn('id', $searchIds)
            ->pluck('id')
            ->toArray();

        $missingIds = array_diff($searchIds, $dbIds);
        if (!empty($missingIds)) {
            throw new ConsoleError(
                __("The following orphan blog post IDs found in the search index: \n\n:ids. \n\nIn order to rebuild the index, run `osm index -f`",
                    ['ids' => implode(', ', $missingIds)]), 1);
        }
    }

    protected function get_db(): Db {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->db;
    }

    protected function get_search(): Search {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->search;
    }

}