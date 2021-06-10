# List queries

This is the 7-th blog post in the "Building `osmcommerce.com`" series. This post is about querying blog post data from indexes and the file system.

{{ toc }}

## The first test

Let's create the first test showcasing the usage:

    <?php
    
    declare(strict_types=1);
    
    namespace My\Tests;
    
    use Illuminate\Support\Collection;
    use My\Posts\Indexer;
    use My\Posts\Posts;
    use Osm\Framework\TestCase;
    
    class test_03_listing extends TestCase
    {
        public string $app_class_name = \My\Samples\App::class;
        public bool $use_db = true;
    
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
    }
    
Basically, after creating a `Posts` object (that is, a collection of posts) and passing it a search index query, you should be able to get all the data of the matching posts through the properties of the `Posts` object. 

Under the hood, the `Posts` object should query the search index and get post IDs, then query the database and get the filenames, and finally, read and parse the actual post files.

It should also deal with index latency - if a file is deleted, it may take a while for it to be deleted from the search index, so the result may contain deleted items.

## `setUp()/tearDown()`

Keep in mind that each test runs in transaction that is automatically rolled back after it ends. It means that after each test, the database remains unchanged. 

While it's handy in database-intensive applications, the other data sources (in our case, the search index), goes out-of-sync with the database.

In order to solve this issue, rebuild the index before, and clear it after running each test. 

Conveniently, PhpUnit run `setUp()` method before each test, and `tearDown()` method after each test, so put the indexing logic there:
 
    protected function setUp(): void {
        parent::setUp();
        Indexer::new()->run();
    }

    protected function tearDown(): void {
        Indexer::new()->clearSearchIndex();
        parent::tearDown();
    }

## `Posts` collection class

Let's implement the post data retrieval logic:

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
     * @property MarkdownParser[] $parsers
     * @property MarkdownParser[] $items
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
                ->map(fn($post) => MarkdownParser::new(['path' => $post->path]))
                ->filter(fn(MarkdownParser $file) => $file->exists);
        }
    
        protected function get_items(): array {
            $items = [];
    
            foreach ($this->search_result->ids as $id) {
                $items[$id] = $this->parsers[$id] ?? null;
            }
    
            return $items;
        }
    } 
    
Just as in `MarkdownParser` class, use lazy properties to retrieve the data only once, and only if it is actually requested by the caller.

 