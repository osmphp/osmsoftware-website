# Schema Migration Tests

In spirit of TDD, I started with a meaningful, but failing test that is reasonably fast. Then, I worked to make it green:

* implemented the loading of schema fixtures from the codebase;
* worked on generating migration files.    

More details:

{{ toc }}

### meta.abstract

In spirit of TDD, I started with a meaningful, but failing test that is reasonably fast. Then, I worked to make it green:

* implemented the loading of schema fixtures from the codebase;
* worked on generating migration files.

## First Schema Test

The strategy is plain simple: write the test code how I'd like it to look, and then think how I should modify Osm Admin code to make it work.

Here is how I'd like them to look like:

    class test_03_strings extends TestCase
    {
        public string $app_class_name = \Osm\Admin\Samples\App::class;
    
        public function test_create_table() {
            // GIVEN empty database
            $this->assertFalse($this->app->db->exists('products'));
    
            // WHEN you run `V1` migration
            $this->loadSchema(Product::class);
            $this->app->schema->generateAndRunMigrations();
    
            // THEN initial product table is created
            $this->assertTrue($this->app->db->exists('products'));
        }
    
        public function test_add_column() {
            // GIVEN database with `V1` schema and some data
            $id = ui_query(Product::class)->insert((object)[
                'title' => 'Lorem ipsum',
            ]);
    
            // WHEN you run `V2` migration
            $this->loadSchema(Product::class, 2);
            $this->app->schema->generateAndRunMigrations();
    
            // THEN new nullable column is added
            $this->assertTrue($this->app->db->connection->getSchemaBuilder()
                ->hasColumn('products', 'description'));
    
            // AND `NULL` value for it is added to existing data
            $this->assertNull($this->app->db->table('products')
                ->where('id', $id)
                ->value('description'));
                
            // FINALLY, clear the cache and DB for other tests    
            $this->app->cache->clear();
            $this->app->migrations()->fresh();
            $this->app->migrations()->up();
        }
    }
    
That's the easy part. Now, the hard one: how to make it work.

### Application Is Instantiated For Each Test Method 

First, let's examine how the test environment is prepared and cleared.

Before every test method, `$osm_app` is instantiated. After each test method it's terminated:

    // Osm\Core\TestCase
    protected function setUp(): void {
        $this->app = Apps::create($this->app_class_name);
        Apps::enter($this->app);
        $this->app->boot();
    }

    protected function tearDown(): void {
        $this->app->terminate();
        Apps::leave();
        $this->app = null;
    }

### Bootstrap Script

Before running the test suite, PHPUnit executes its bootstrap script.

Currently, the `tests_migrations/bootstrap.php` script clears the cache and runs module migrations (*not* schema migrations):

    $app->cache->clear();
    $app->migrations()->fresh();
    $app->migrations()->up();

It means that every test expects clear database with all the tables that modules create.

### Migration Tests Are Compliant With Bootstrap Script

The first test expects empty database:

    // GIVEN empty database
    $this->assertFalse($this->app->db->exists('products'));

The last test clears the database for other tests:

    // FINALLY, clear the cache and DB for other tests
    $this->app->cache->clear();
    $this->app->migrations()->fresh();
    $this->app->migrations()->up();

### Tests Migrate A Single Version At A Time

The first test migrates the `V1` version of the schema, the second test migrates the `V2` version of the schema, and so on:

    // the first test
    $this->loadSchema(Product::class);
    $this->app->schema->generateAndRunMigrations();

    // the second test
    $this->loadSchema(Product::class, 2);
    $this->app->schema->generateAndRunMigrations();

    ...
    
### Class Name References Currently Loaded Version

For convenience, you can import any version of the `Product` class and use it in `query()` and `ui_query` function calls.

It resolves to the data class definition of the currently loaded version.

## Migration Test Support

### Loading Schema Fixture

Currently, the `$osm_app->schema` is loaded as follows:

    // Osm\Admin\Schema\Traits\AppTrait
    protected function get_schema(): Schema {
        return Schema::new()->parse();
    }

    // Osm\Admin\Schema\Schema
    public function parse(): static {
        global $osm_app; /* @var App $osm_app */
        ...
        $recordClass = $osm_app->classes[Record::class];

        foreach ($recordClass->child_class_names as $baseClassName) {
            $this->parseTable($osm_app->classes[$baseClassName]);
        } 
        ...
    }

In production, this code should omit record classes marked with `#[Fixture]` attribute.

In test code, this code should only load `#[Fixture]` record classes in a specified namespace.

`$this->loadSchema(Product::class)` forces fixture loading by setting new `fixture_*` properties:

    protected function loadSchema(string $className, int $version = 1): void {
        $this->app->cache->clear();
        $this->app->schema = $this->app->cache->get('schema', fn() =>
            Schema::new([
                'fixture_class_name' => $className,
                'fixture_version' => $version,
            ])->parse()
        );
    }
 
Back to `Schema::parse()`, a check is added - whether a record class belongs to the currently loaded schema:

    foreach ($recordClass->child_class_names as $baseClassName) {
        if ($this->belongs($baseClassName)) {
            $this->parseTable($osm_app->classes[$baseClassName]);
        }
    }
    ...
    protected function belongs(mixed $className): bool {
        global $osm_app; /* @var App $osm_app */

        $class = $osm_app->classes[$className];

        if ($this->fixture_version_namespace) {
            // in a schema migration test, load all record classes marked with
            // the `#[Fixture]` attribute and under the
            // fixture version namespace, for example,
            // `\Osm\Admin\Samples\Migrations\String_\V001\`
            return isset($class->attributes[Fixture::class]) &&
                str_starts_with($className, $this->fixture_version_namespace);
        }
        else {
            // in production, load all record classes except having the
            // `#[Fixture]` attribute
            return !isset($class->attributes[Fixture::class]);
        }
    }

### Fixture Namespaces
 
Every fixture data class has two namespaces:

    // actual fixture class name
    Osm\Admin\Samples\Migrations\String_\V001\Product 

    // fixture namespace
    Osm\Admin\Samples\Migrations\String_\ 

    // fixture version namespace
    Osm\Admin\Samples\Migrations\String_\V001\ 

### Registered Fixture Class Name

In every schema version, the same class has unique class name:     

    // actual fixture class name
    Osm\Admin\Samples\Migrations\String_\V001\Product 

However, in schema, it's registered under version-independent class name:

    // registered fixture class name
    Osm\Admin\Samples\Migrations\String_\Product 
    
Two new schema methods convert class name to version-independent class name and back:

    // Osm\Admin\Schema\Schema
    public function getUnversionedName(string $className): string {
        if (!$this->fixture_class_name) {
            return $className;
        }

        return $this->fixture_namespace . substr($className,
            strlen($this->fixture_version_namespace));
    }

    public function getVersionedName(string $className): string {
        if (!$this->fixture_class_name) {
            return $className;
        }

        return $this->fixture_version_namespace . substr($className,
                strlen($this->fixture_namespace));
    }

## Generating Migration Files

The "only" thing left to do is generating and running migrations in tests:

    $this->app->schema->generateAndRunMigrations();

There will be two separate commands in the command line, so this method calls two separate methods each creating and "running" an object:

    // Osm\Admin\Schema\Schema
    public function generateAndRunMigrations(): void {
        $this->generateMigrations();
        $this->runMigrations();
    }

    public function generateMigrations(): void {
        Generator::new(['schema' => $this])->run();
    }

    public function runMigrations(): void {
        Migrator::new(['schema' => $this])->run();
    }


### Only Regular Schema Migration Should Be Under Git

Earlier, I came to a conclusion that [migration files should be under Git](07-data-main-ideas-of-schema-change-migrations.md#same-migrations-in-production) in order to have the same migrations in production.

Now I'm thinking that it's only required for regular schema migrations. Schema fixture migrations won't run in production, so they don't need to be under version control.

Hence, there will be two directories containing schema migrations:

    // regular schema migrations
    migrations/{app_name}/M000000001.php
    
    // schema fixture migrations
    temp/{app_name}/migrations/M000000001.php
    
### Migration Model

To render a Web page, you need a data model that closely resembles Web page structure. Similarly, to generate migrations, you also need a data model that is convenient to generate source code from. 

As I generate a list of files, let's generate each file from a `Migration` object:

    /**
     * @property Schema $schema
     * @property Generator\Migration[] $migrations
     */
    class Generator extends Object_
    {
        protected function get_schema(): Schema {
            throw new Required(__METHOD__);
        }
    
        public function run(): void {
            foreach ($this->migrations as $migration) {
                $migration->generate();
            }
        }
        
        protected function get_migrations(): array {
            throw new NotImplemented($this);
        }
    }
    
    class Generation\Migration extends Object_
    {
        public function generate(): void {
            throw new NotImplemented($this);
        }
    }