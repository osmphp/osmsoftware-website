<?php

declare(strict_types=1);

namespace Osm\Docs\Docs\Migrations;

use Osm\Core\App;
use Osm\Framework\Migrations\Migration;
use Osm\Framework\Search\Blueprint;
use Osm\Framework\Search\Search;

/**
 * @property Search $search
 */
class M02_docs__search extends Migration
{
    protected function get_search(): Search {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->search;
    }

    public function create(): void {
        if ($this->search->exists('docs')) {
            $this->search->drop('docs');
        }

        $this->search->create('docs', function (Blueprint $index) {
            $index->string('title')
                ->searchable();
            $index->string('text')
                ->searchable();
        });
    }

    public function drop(): void {
        $this->search->drop('docs');
    }
}