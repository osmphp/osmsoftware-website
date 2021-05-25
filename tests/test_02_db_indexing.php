<?php

declare(strict_types=1);

namespace My\Tests;

use Osm\Framework\TestCase;
use My\Posts\Db;

class test_02_db_indexing extends TestCase
{
    public string $app_class_name = \My\Samples\App::class;
    public bool $use_db = true;

    public function test_db_indexing_one_file() {
        // GIVEN the sample posts

        // WHEN you index the `welcome.md`
        Db\Indexer::new(['path' => '21/05/18-welcome.md'])->run();

        // THEN it's in the database
        $this->assertTrue($this->db->table('posts')
            ->where('path', '21/05/18-welcome.md')
            ->exists()
        );
    }

    public function test_db_indexing_all_files() {
        // GIVEN the sample posts

        // WHEN you index the `welcome.md`
        Db\Indexer::new()->run();

        // THEN they all are in the database
        $this->assertTrue($this->db->table('posts')
            ->where('path', '21/05/18-welcome.md')
            ->exists()
        );
    }

    public function test_marking_deleted_files_in_db_index() {
        // GIVEN the sample posts and a record about a file that doesn't exist
        $this->db->table('posts')->insert([
            'path' => 'fake.md',
        ]);

        // WHEN you index the the blog posts
        Db\Indexer::new()->run();

        // THEN the database record is marked as deleted
        $this->assertEquals(1, $this->db->table('posts')
            ->where('path', 'fake.md')
            ->value('deleted')
        );
    }
}