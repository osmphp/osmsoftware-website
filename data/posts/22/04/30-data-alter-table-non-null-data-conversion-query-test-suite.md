# ALTER TABLE. Non-NULL Data Conversion. Query Test Suite

Implemented in Osm Admin:

* new ALTER TABLE algorithm
* NULL to non-NULL data conversion
* new query test suite

More details:

{{ toc }}

### meta.abstract

Implemented in Osm Admin:

* new ALTER TABLE algorithm
* NULL to non-NULL data conversion
* new query test suite

## New ALTER TABLE Algorithm

Previously, I came to a conclusion that a table migration should consist of four steps: pre-migration, conversion, validation, and post-migration.

It's not exactly true. If a table is new, there is no data to be converted and validated, and, hence, there is no need to issue DDL statements twice. However, if a table already exists, all these steps are needed.

### `Diff\Table::alter()`

These four steps are invoked in the `alter()` method:

    // Osm\Admin\Schema\Diff\Table
    protected function alter(): void {
        $this->preAlter();
        $this->convert();
        $this->validate();
        $this->postAlter();
    }

The `preAlter()` and `postAlter()` methods are very similar:

    protected function preAlter(): void {
        if ($this->requires_pre_alter) {
            $this->db->alter($this->new->table_name, function(Blueprint $table) {
                foreach ($this->properties as $property) {
                    if (!$property->new->explicit) {
                        continue;
                    }

                    $property->migrate($property->old?->explicit
                        ? Property::PRE_ALTER
                        : Property::CREATE, $table);
                }
            });
        }
    }

    protected function postAlter(): void {
        if ($this->requires_post_alter) {
            $this->db->alter($this->new->table_name, function(Blueprint $table) {
                foreach ($this->properties as $property) {
                    if (!$property->new->explicit) {
                        continue;
                    }

                    if ($property->old?->explicit) {
                        $property->migrate(Property::POST_ALTER, $table);
                    }
                }
            });
        }
    }

For the record, table creation has changed a bit as well:

    protected function create(): void {
        $this->db->create($this->new->table_name, function(Blueprint $table) {
            foreach ($this->properties as $property) {
                if (!$property->new->explicit) {
                    continue;
                }

                $property->migrate(Property::CREATE, $table);
            }

            $table->json('_data')->nullable();
            $table->json('_overrides')->nullable();
        });
    }

### Property Migration Mode

As you can see, `Property::migrate()` method is called with additional mode parameter:

* `Property::CREATE` - create new property
* `Property::PRE_ALTER` - modify property in the pre-alter phase
* `Property::POST_ALTER` - modify property in the post-alter phase

Here is how it looks:

    public function migrate(string $mode, Blueprint $table = null): bool {
        throw new NotImplemented($this);
    }

### `require_pre/post_alter`

Another change in this method is that it's used to determine is anything should be migrated at all. If so, it returns `true`. While detecting the need for migration, Osm Admin passes `null` to the second parameter.

Here is how it's used:

    protected function get_requires_pre_alter(): bool {
        foreach ($this->properties as $property) {
            if (!$property->new->explicit) {
                continue;
            }

            if (!$property->old?->explicit) {
                return true;
            }

            if ($property->migrate(Property::PRE_ALTER)) {
                return true;
            }
        }

        return false;
    }

    protected function get_requires_post_alter(): bool {
        foreach ($this->properties as $property) {
            if (!$property->new->explicit) {
                continue;
            }

            if (!$property->old?->explicit) {
                continue;
            }

            if ($property->migrate(Property::POST_ALTER)) {
                return true;
            }
        }

        return false;
    }
 
### Property Migrations

`Property::migrate()` is different for every property type. For example, `string` properties are migrated as follows:

    public function migrate(string $mode, Blueprint $table = null): bool {
        // if it's a new property, migration should run no matter what
        $run = $mode === static::CREATE;

        $column = $this->column($table);
        $run = $this->nullable($mode, $column) || $run;
        $this->change($mode, $column);

        return $run;
    }

### `nullable()` And Other Column Attributes

`Property::migrate()` analyze every column attribute changes and add migrations accordingly. For example, here is how column nullability is migrated:

    protected function nullable(string $mode, ?ColumnDefinition $column): bool {
        $changed = $mode === static::CREATE ||
            $this->old->actually_nullable != $this->new->actually_nullable;

        // defer conversion from nullable to non-nullable from pre-alter
        // to post-alter phase
        $deferred = $mode !== static::CREATE &&
            $this->old->actually_nullable &&
            !$this->new->actually_nullable;

        $column?->nullable($deferred
            ? $mode === static::PRE_ALTER
            : $this->new->actually_nullable);

        return match($mode) {
            static::CREATE => true,
            static::PRE_ALTER => $changed && !$deferred,
            static::POST_ALTER => $changed && $deferred,
        };
    }

## Data Conversions

All good! I've split ALTER migration into "pre" and "post" phases, and they behave under debugger as expected. Now it's time to implement data conversion between these steps. 

Now, it's pretty empty:

    // Osm\Admin\Schema\Diff\Table
    protected function convert(): void {
    }

### Main Idea

This method should run an UPDATE:

    UPDATE products
    SET description = IF(description IS NULL, '-', description)
    
`query()` formula syntax should be something like:

    query(Product::class)
        ->select("description ?? '-' AS description")
        ->bulkUpdate();    
    
The current `update()` method only accepts literals, so it can't be used.

### `Table::convert()`

Before going into the `bulkUpdate()` SQL generation, let's prepare the query in the table migration.

Here is goes:

    // Osm\Admin\Schema\Diff\Table
    protected function convert(): void {
        $query = Query::new(['table' => $this->new]);

        if ($this->requires_convert) {
            foreach ($this->properties as $property) {
                $property->convert($query);
            }

            $query->bulkUpdate();
        }
    }

The same principle of one method answering two questions is used here. When a `$query` parameter is provided, `$property->convert()` method prepares the conversion query. If it's not provided, it returns `true` if a property requires data conversion:

    protected function get_requires_convert(): bool {
        foreach ($this->properties as $property) {
            if ($property->convert()) {
                return true;
            }
        }

        return false;
    }

### `Property::convert()`

`Property::convert()` method is different for every method. For example, the `string` type:

    public function convert(Query $query = null): bool {
        $formula = $this->new->name;

        $formula = $this->convertToNonNull($formula);

        if ($query && $formula !== $this->new->name) {
            $query->select("{$formula} AS {$this->new->name}");
        }

        return $formula !== $this->new->name;
    }

### Conversion Triggers

This method check various conversion triggers, and if it detects one, a formula is added to the query, and the `convert()` method returns `true`. For example, here is how Osm Admin detects that nullable property becomes non-nullable:

    protected function convertToNonNull(string $formula): string {
        if (!$this->old) {
            return $formula;
        }

        $makeNonNull = $this->old->actually_nullable &&
            !$this->new->actually_nullable;

        return $makeNonNull
            ? "{$formula} ?? {$this->non_null_formula}"
            : $formula;
    }

**Notice**. For conversion, it doesn't matter if a property is explicit - it works both with explicit columns, and properties stored in the `_data` JSON column.

## Query Test Suite

### Chicken-Egg Paradox

As with well-known chicken-egg paradox, I have a dilemma. Migration unit tests assume that formula queries work well. Formula query unit tests assume that migrations work well.

The solution is to test formula queries on a schema that is migrated without data conversion, that is, that only use `CREATE TABLE` statements.

Let's create a schema for testing queries:

    <?php
    
    namespace Osm\Admin\Samples\Queries\V001;
    
    use Osm\Admin\Schema\Attributes\Fixture;
    use Osm\Admin\Schema\Record;
    use Osm\Admin\Schema\Attributes\Explicit;
    
    /**
     * @property ?string $description #[Explicit]
     *
     * @uses Explicit
     */
    #[Fixture]
    class Product extends Record
    {
    
    }
    
It's quite simple now, I will extend it as needed.   

### Bootstrap Script

Testing queries requires a specific preparation, or how they call it, a bootstrap script:

    // tests_queries/bootstrap.php
    <?php
    
    declare(strict_types=1);
    
    use Osm\Admin\Samples\App;
    use Osm\Admin\Schema\Schema;
    use Osm\Runtime\Apps;
    
    require 'vendor/autoload.php';
    umask(0);
    
    try {
        Apps::$project_path = dirname(__DIR__);
        Apps::compile(App::class);
        Apps::run(Apps::create(App::class), function(App $app) {
            $app->cache->clear();
            $app->migrations()->fresh();
            $app->migrations()->up();
    
            $app->schema = $app->cache->get('schema', fn() =>
                Schema::new([
                    'fixture_class_name' => 
                        \Osm\Admin\Samples\Queries\V001\Product::class,
                    'fixture_version' => 1,
                ])->parse()
            );
    
            $app->schema->migrate();
        });
    }
    catch (Throwable $e) {
        echo "{$e->getMessage()}\n{$e->getTraceAsString()}\n";
        throw $e;
    }

### Configuration

The next step is to create a PHPUnit configuration file:
    
    // phpunit_queries.xml
    <?xml version="1.0" encoding="UTF-8"?>
    <phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
             xsi:noNamespaceSchemaLocation="http://schema.phpunit.de/4.1/phpunit.xsd"
             colors="true"
             backupGlobals="false"
             bootstrap="tests_queries/bootstrap.php"
             stopOnFailure="true">
        <testsuite name="all">
            <directory prefix="test_" suffix=".php">tests_queries</directory>
        </testsuite>
    </phpunit>

### Namespace Root
    
Then register a namespace root in the `composer.json` file:

    {
        ...
        "autoload-dev": {
            "psr-4": {
                ...
                "Osm\\Admin\\TestsQueries\\": "tests_queries/",
                ...
            }
        }
    }

And run `composer update`.

### First Test

Create the first test:

    <?php
    
    namespace Osm\Admin\TestsQueries;
    
    use Osm\Admin\Samples\Generics\Item;
    use Osm\Admin\Samples\Queries\V001\Product;
    use Osm\Framework\TestCase;
    use function Osm\query;
    
    class test_01_sql_generation extends TestCase
    {
        public string $app_class_name = \Osm\Admin\Samples\App::class;
        public bool $use_db = true;
    
        public function test_zero_count(): void {
            // GIVEN a schema defined in the `Osm\Admin\Samples\Queries\V001`
            // namespace
    
            // WHEN you count records in an empty table
            $count = query(Product::class)
                ->value("COUNT() AS count");
    
            // THEN it's 0
            $this->assertEquals(0, $count);
        }
    }
    
Here, the `use_db` flag runs every test in a transaction that is rolled back when the test ends.

Check if the test passes:

    vendor/bin/phpunit -c phpunit_queries.xml
    
And, yes, it does!

