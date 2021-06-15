# Database indexing and migrations

This is the 5-th blog post in the "Building `osmcommerce.com`" series. This post covers database indexing and migrations.

{{ toc }}

## meta

    {
        "series": "Building osmcommerce.com", 
        "series_part": 5
    }

## Motivation

Later, when implementing a comment system, each comment will be associated with a blog post, and for that, a unique ID is needed. Markdown file path may change, so it's not a good option. Instead, each blog post is registered in the database `posts` table, and a record ID is used as blog post's unique identifier.

Indexing is a process of keeping blog post files and the `posts` table in sync.

## Starting with a test

Create a test file `tests/test_02_db_indexing.php` for a yet-to-be-created `Db\Indexer` class that'll be responsible for the indexing logic:

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
    
**Note**. Every test runs in a transaction, and in the end of every test the transaction is rolled back. This allows resetting the database to a fresh state really fast.
    
## Migrations

Tests expect the `post` table to be in the database. So let's define a **migration** - a class that creates the table, and run it before running tests - in `tests/bootstrap.php` file.

1. Create a migration file, it should follow `src/{module}/Migrations/M{nn}_{name}.php` file naming convention:

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
                    $table->boolean('deleted')->default(false);
                });
            }
        
            public function drop(): void {
                $this->db->drop('posts');
            }
        }

2. Run the migrations from the `tests/bootstrap.php` file:

        ...
        Apps::run(Apps::create(App::class), function(App $app) {
            ...
            $app->migrations()->up();
        });

The `up()` method runs `create()` method in every migration class of every active module, including `M01_posts`.

## `Indexer` class

`Indexer` class, responsible for the indexing logic, will access the database. By convention, all classes that deal with the database reside in the `Db` namespace.

To use this class, create its instance and assign its `path` property to point to a file or directory to be indexed, and then call its `run()` method.

The underlying logic is straightforward:

1. Register each file in `path` in the table.
2. Check if files registered in the table are not in the file system anymore, and if so, mark them as deleted.

Check the whole code [here](https://github.com/osmphp/osmcommerce-website/blob/v0.1/src/Posts/Db/Indexer.php). 

Some things should be revisited later:

1. Subsequent reindexing should be incremental, that is, only check things that changed since last indexing.
2. `deleted` should become `deleted_at` timestamp. After X days, marked as deleted posts should be actually deleted.