<?php

declare(strict_types=1);

namespace Osm\Docs\Docs\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Osm\Core\App;
use Osm\Framework\Db\Db;
use Osm\Framework\Migrations\Migration;

/**
 * @property Db $db
 */
class M01_docs extends Migration
{
    protected function get_db(): Db {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->db;
    }

    public function create(): void {
        $this->db->create('docs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('book')->index();
            $table->string('version')->index();
            $table->string('path')->index();
            $table->unique(['book', 'version', 'path']);
            $table->string('parent_url')->nullable()->index();
            $table->string('url')->index();
            $table->integer('sort_order')->nullable()->index();
            $table->dateTime('modified_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    public function drop(): void {
        $this->db->drop('docs');
    }
}