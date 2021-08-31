<?php

declare(strict_types=1);

namespace My\Tests;

use My\Posts\PageType;
use My\Posts\Posts;
use Osm\Framework\TestCase;

class test_03_filter_parsing extends TestCase
{
    public string $app_class_name = \My\Samples\App::class;

    public function test_unparsed_filter_values() {
        // GIVEN a collection with sample http query
        $posts = Posts::new([
            'page_type' => PageType\Search::new(),
            'http_query' => [
                'q' => 'dynamic route',
                'category' => 'status-reports framework',
                'date' => '2020 2021-01 2021-02',
            ],
        ]);

        // WHEN you access un-parsed filter values
        // THEN they are set as expected
        $this->assertEquals('dynamic route',
            $posts->filters['q']->unparsed_value);
        $this->assertEquals('status-reports framework',
            $posts->filters['category']->unparsed_value);
        $this->assertEquals('2020 2021-01 2021-02',
            $posts->filters['date']->unparsed_value);
    }

    public function test_ignored_unparsed_search_filter_values() {
        // GIVEN a collection with sample http query
        $posts = Posts::new([
            'page_type' => PageType\Home::new(),
            'http_query' => [
                'q' => 'dynamic route',
            ],
        ]);

        // WHEN you access un-parsed search filter value
        // THEN they it's not returned, as search parameter on
        // the home page is ignored
        $this->assertNull($posts->filters['q']->unparsed_value);
    }

    public function test_parsed_search_filter_value() {
        // GIVEN a collection with sample http query
        $posts = Posts::new([
            'page_type' => PageType\Search::new(),
            'http_query' => [
                'q' => 'dynamic route',
            ],
        ]);

        // WHEN you access un-parsed filter values
        // THEN they are set as expected
        $this->assertCount(1,
            $posts->filters['q']->applied_filters);

        $this->assertEquals('dynamic route',
            $posts->filters['q']->applied_filters[0]->phrase);
    }

    public function test_ignored_unparsed_category_filter_values() {
        // GIVEN a collection with sample http query
        $posts = Posts::new([
            'page_type' => PageType\Category::new([
                'category_url_key' => 'osmsoftware-website',
            ]),
            'http_query' => [
                'category' => 'status-reports framework',
            ],
        ]);

        // WHEN you access un-parsed search filter value
        // THEN they it's not returned, as search parameter on
        // the home page is ignored
        $this->assertNull($posts->filters['category']->unparsed_value);
    }

    public function test_parsed_category_page_type_value() {
        // GIVEN a collection with sample http query
        $posts = Posts::new([
            'page_type' => PageType\Category::new([
                'category_url_key' => 'osmsoftware',
            ]),
        ]);

        // WHEN you access un-parsed filter values
        // THEN they are set as expected
        $this->assertCount(1,
            $posts->filters['category']->applied_filters);

        $this->assertEquals('osmsoftware',
            $posts->filters['category']->applied_filters[0]->category->url_key);
    }

    public function test_parsed_category_filter_values() {
        // GIVEN a collection with sample http query
        $posts = Posts::new([
            'page_type' => PageType\Home::new(),
            'http_query' => [
                'category' => 'status-reports framework',
            ],
        ]);

        // WHEN you access un-parsed filter values
        // THEN they are set as expected
        $this->assertCount(2,
            $posts->filters['category']->applied_filters);

        $this->assertEquals('status-reports',
            $posts->filters['category']->applied_filters[0]->category->url_key);
        $this->assertEquals('framework',
            $posts->filters['category']->applied_filters[1]->category->url_key);
    }

    public function test_ignored_unparsed_date_filter_values() {
        // GIVEN a collection with sample http query
        $posts = Posts::new([
            'page_type' => PageType\Year::new([
                'year' => 2021,
            ]),
            'http_query' => [
                'date' => '2020 2021-01 2021-02',
            ],
        ]);

        // WHEN you access un-parsed search filter value
        // THEN they it's not returned, as search parameter on
        // the home page is ignored
        $this->assertNull($posts->filters['date']->unparsed_value);
    }

    public function test_parsed_year_page_type_value() {
        // GIVEN a collection with sample http query
        $posts = Posts::new([
            'page_type' => PageType\Year::new([
                'year' => 2021,
            ]),
        ]);

        // WHEN you access un-parsed filter values
        // THEN they are set as expected
        $this->assertCount(1,
            $posts->filters['date']->applied_filters);

        $this->assertEquals(2021,
            $posts->filters['date']->applied_filters[0]->year);
        $this->assertNull($posts->filters['date']->applied_filters[0]->month);
    }

    public function test_parsed_month_page_type_value() {
        // GIVEN a collection with sample http query
        $posts = Posts::new([
            'page_type' => PageType\Month::new([
                'year' => 2021,
                'month' => 6,
            ]),
        ]);

        // WHEN you access un-parsed filter values
        // THEN they are set as expected
        $this->assertCount(1,
            $posts->filters['date']->applied_filters);

        $this->assertEquals(2021,
            $posts->filters['date']->applied_filters[0]->year);
        $this->assertEquals(6,
            $posts->filters['date']->applied_filters[0]->month);
    }

    public function test_parsed_date_filter_values() {
        // GIVEN a collection with sample http query
        $posts = Posts::new([
            'page_type' => PageType\Home::new(),
            'http_query' => [
                'date' => '2020 2021-01 2021-02',
            ],
        ]);

        // WHEN you access un-parsed filter values
        // THEN they are set as expected
        $this->assertCount(3,
            $posts->filters['date']->applied_filters);

        $this->assertEquals(2020,
            $posts->filters['date']->applied_filters[0]->year);
        $this->assertNull($posts->filters['date']->applied_filters[0]->month);

        $this->assertEquals(2021,
            $posts->filters['date']->applied_filters[1]->year);
        $this->assertEquals(1,
            $posts->filters['date']->applied_filters[1]->month);

        $this->assertEquals(2021,
            $posts->filters['date']->applied_filters[2]->year);
        $this->assertEquals(2,
            $posts->filters['date']->applied_filters[2]->month);
    }
}