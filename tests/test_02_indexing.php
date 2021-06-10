<?php

declare(strict_types=1);

namespace My\Tests;

use My\Posts\Indexer;
use Osm\Framework\TestCase;

class test_02__indexing extends TestCase
{
    public string $app_class_name = \My\Samples\App::class;
    public bool $use_db = true;

    protected function tearDown(): void {
        Indexer::new()->clearSearchIndex();
        parent::tearDown();
    }

    public function test_db_indexing_one_file() {
        // GIVEN the sample posts

        // WHEN you index the `welcome.md`
        Indexer::new(['path' => '21/05/18-welcome.md'])->run();

        // THEN it's in the database
        $this->assertNotNull($id = $this->db->table('posts')
            ->where('path', '21/05/18-welcome.md')
            ->value('id')
        );

        // AND in search
        $this->assertNotNull($this->app->search->index('posts')
            ->where('id', '=', $id)
            ->id()
        );
    }

    public function test_db_indexing_all_files() {
        // GIVEN the sample posts

        // WHEN you index the `welcome.md`
        Indexer::new()->run();

        // THEN they all are in the database
        $this->assertNotNull($id = $this->db->table('posts')
            ->where('path', '21/05/18-welcome.md')
            ->value('id')
        );

        // AND in search
        $this->assertNotNull($this->app->search->index('posts')
            ->where('id', '=', $id)
            ->id()
        );
    }

    public function test_marking_deleted_files_in_db_index() {
        // GIVEN the sample posts and a record about a file that doesn't exist
        $this->db->table('posts')->insert([
            'path' => 'fake.md',
        ]);

        // WHEN you index the the blog posts
        Indexer::new()->run();

        // THEN the database record is marked as deleted
        $post = $this->db->table('posts')
            ->where('path', 'fake.md')
            ->first(['id', 'deleted_at']);

        $this->assertNotNull($post);
        $this->assertNotNull($post->deleted_at);

        // AND search entry is deleted
        $this->assertNull($this->app->search->index('posts')
            ->where('id', '=', $post->id)
            ->id()
        );
    }
}