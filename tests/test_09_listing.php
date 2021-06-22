<?php

declare(strict_types=1);

namespace My\Tests;

use Illuminate\Support\Collection;
use My\Posts\Indexer;
use My\Posts\Posts;
use Osm\Framework\TestCase;

class test_09_listing extends TestCase
{
    public string $app_class_name = \My\Samples\App::class;
    public bool $use_db = true;

    protected function setUp(): void {
        parent::setUp();
        Indexer::new()->run();
    }

    protected function tearDown(): void {
        Indexer::new()->clearSearchIndex();
        parent::tearDown();
    }

    public function test_month() {
        // GIVEN the sample posts are indexed

        // WHEN you retrieve the posts for a given month
        $posts = Posts::new([
            'search_query' => $this->app->search->index('posts')
                ->where('month', '=', '2021-05')
                ->orderBy('created_at', desc: true)
                ->limit(5),
        ]);

        // THEN their data is loaded into memory from the search engine,
        // the database, and files
        $this->assertEquals(6, $posts->count);
        $this->assertCount(5, $posts->items);
        $this->assertEquals([
            'Database indexing and migrations',
            'Configuration, unit testing, Markdown file parsing',
            'Home page',
            'Plan of attack',
            'Requirements',
        ], (new Collection($posts->items))->pluck('title')->toArray());
    }

    public function test_series() {
        // GIVEN the sample posts are indexed

        // WHEN you retrieve the posts for a given month
        $posts = Posts::new([
            'search_query' => $this->app->search->index('posts')
                ->where('series', '=', 'building-osmcommerce-com')
                ->orderBy('created_at', desc: true)
                ->limit(5),
        ]);

        // THEN their data is loaded into memory from the search engine,
        // the database, and files
        $this->assertEquals(1, $posts->count);
        $this->assertCount(1, $posts->items);
        $this->assertEquals([
            'Requirements',
        ], (new Collection($posts->items))->pluck('title')->toArray());
    }

    public function test_search() {
        // GIVEN the sample posts are indexed

        // WHEN you retrieve the posts for a given month
        $posts = Posts::new([
            'search_query' => $this->app->search->index('posts')
                ->search('create module')
                ->limit(5),
        ]);

        // THEN their data is loaded into memory from the search engine,
        // the database, and files
        $titles = (new Collection($posts->items))->pluck('title')->toArray();
        $this->assertContains(
            'Configuration, unit testing, Markdown file parsing',
            $titles);
        $this->assertContains(
            'Home page',
            $titles);
    }
}