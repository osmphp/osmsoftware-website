<?php

declare(strict_types=1);

namespace My\Tests;

use Illuminate\Support\Collection;
use My\Posts\Indexer;
use My\Posts\PageType;
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
            'page_type' => PageType\Month::new([
                'year' => 2021,
                'month' => 5,
            ]),
            'http_query' => [],
            'limit' => 5,
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

    public function test_category() {
        // GIVEN the sample posts are indexed

        // WHEN you retrieve the posts for a given month
        $posts = Posts::new([
            'page_type' => PageType\Category::new([
                'category_url_key' => 'osmcommerce-com',
            ]),
            'http_query' => [],
            'limit' => 5,
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
            'page_type' => PageType\Search::new(),
            'http_query' => [
                'q' => 'create module',
            ],
            'limit' => 5,
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