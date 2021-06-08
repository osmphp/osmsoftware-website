<?php

declare(strict_types=1);

namespace My\Posts\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Osm\Core\App;
use Osm\Framework\Db\Db;
use Osm\Framework\Migrations\Migration;

/**
 * @property Db $db
 */
class M01_posts extends Migration
{
    protected function get_db(): Db {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->db;
    }

    public function create(): void {
        $this->db->create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('path')->unique();
            $table->dateTime('modified_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    public function drop(): void {
        $this->db->drop('posts');
    }
}