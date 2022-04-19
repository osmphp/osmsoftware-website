# Fixing Issues. Schema Diff

Recently, I fixed numerous issues to make the first migration tests work.

Then, I started implementing the schema diff algorithm - the basis for schema migrations.

Contents:

{{ toc }}

### meta.abstract

Recently, I fixed numerous issues to make the first migration tests work.

Then, I started implementing the schema diff algorithm - the basis for schema migrations.

## Fixing Issues

On TDD front, I have already implemented the [loading of schema fixtures from the codebase](10-data-schema-migration-tests.md), and the first tests. 

They don't work. Let's fix issues, one by one.

### Undefined Array Key `Osm\Admin\Samples\Migrations\String_\ct`

The error happens in the following code:

    $id = ui_query(Product::class)->insert((object)[
        'title' => 'Lorem ipsum',
    ]);
    
It seems that `Product::class`, which is `Osm\Admin\Samples\Migrations\String_\V001\Product`, is converted to version-independent class name incorrectly.

Indeed, there was a logical error in

    // Osm\Admin\Schema\Schema
    public function getUnversionedName(string $className): string {
        if (!$this->fixture_class_name) {
            return $className;
        }

        return $this->fixture_namespace . substr($className,
            strlen($this->fixture_version_namespace));
    }

Here is the fixed version:

    public function getUnversionedName(string $className): string {
        if (!$this->fixture_class_name) {
            return $className;
        }

        return $this->fixture_namespace . substr($className,
            strlen($this->fixture_namespace));
    }

### Failed Asserting That False Is True At `test_03_strings.php:39`

`THEN` part fails:

    public function test_add_column() {
        ...
        // WHEN you run `V2` migration
        $this->loadSchema(Product::class, 2);
        $this->app->schema->migrate();

        // THEN new nullable column is added
        $this->assertTrue($this->app->db->connection->getSchemaBuilder()
            ->hasColumn('products', 'description'));
        ...
    }

Actually, this test should have failed earlier, at `$this->loadSchema(Product::class, 2)`, as I haven't defined the second version in sample code yet. Why didn't it?
    
**Side note**. I can't throw out a joke about debugging out of my head that I saw somewhere. Here it is:   

> Debugging - the classic mystery game where you are the detective, the victim, and the murderer.

### Undefined Schema Version Fixtures  

Back to `loadSchema()`.

Indeed, in case I forget to define the version namespace, `Schema::parse()` doesn't find any data classes, and considers that I intended empty schema. But that's not what I intended!

Now, there is an additional check for that:

    // Osm\Admin\Schema\Schema
    public function parse(): static {
        ...
        if ($this->fixture_version_namespace) {
            // in a schema migration test, fail if there is not a single
            // data class defined for a given fixture version
            if (empty($this->tables)) {
                throw new InvalidFixture(__(
                    "No data classes defined in the ':namespace' schema fixture version namespace",
                    ['namespace' => $this->fixture_version_namespace]));
            }
        }
        ...
    }

### Defining Second Schema Fixture

OK, Osm Admin tells me that the second version of the schema fixture is not defined. Nice! Let;s define it:

    // Osm\Admin\Samples\Migrations\String_\V002\Product
    
    /**
     * @property ?string $description #[Explicit] 
     * 
     * @uses Explicit
     */
    #[Fixture]
    class Product extends Record
    {
    
    }

### Base Table Or View Already Exists

Now it complains that the `products` table already exists. Of course, it does! Why Osm Admin doesn't alter it?

It turns out, indexes in `$schema->tables` are still version-specific, and they shouldn't be. 

I fixed the conversion of a version-specific name to a generic one:

    // Osm\Admin\Schema\Schema
    public function getUnversionedName(string $className): string {
        if (!$this->fixture_class_name) {
            return $className;
        }

        return $this->fixture_namespace . substr($className,
            strlen($this->fixture_version_namespace));
    }

### Undefined Array Key `Osm\Admin\Samples\Migrations\String_\ct`, Again

Wait a minute, I've already been there. It seems the fix I did the other way was incorrect.

The correct fix is to only "unversion" class names that have version namespace:

    public function getUnversionedName(string $className): string {
        if (!$this->fixture_class_name) {
            return $className;
        }

        if (!$this->isNameVersioned($className)) {
            return $className;
        }

        return $this->fixture_namespace . substr($className,
            strlen($this->fixture_version_namespace));
    }

    public function isNameVersioned(string $className): bool {
        return (bool)preg_match('/\\\\V\d{3}\\\\/', $className);
    }

### Argument `$current` Must Be `?Osm\Admin\Schema\Schema`, `\stdClass` Given 

Finally, I've come up to migration code, and got the error:

    TypeError : Osm\Admin\Schema\Schema::migrateUp(): Argument #1 ($current) 
        must be of type ?Osm\Admin\Schema\Schema, stdClass given, 
        called in /home/vo/projects/admin2/src/Schema/Schema.php on line 71

Indeed, I've recently decided that [the "old" schema should be processed dehydrated](08-data-schema-fixtures.md#need-for-schema-fixtures), but the current method signatures don;t reflect that. Let's fix it:

    // Osm\Admin\Schema\Schema
    protected function migrateUp(\stdClass|Schema|null $current): void
    {
        ...
    }

    // Osm\Admin\Schema\Table    
    public function alter(\stdClass|Table $current): void
    {
        ...
    }

## Schema Diff

### `Table::alter()`

Method signatures are fixed, and I've got to the unimplemented method:

    public function alter(\stdClass|Table $current): void
    {
        throw new NotImplemented($this);
    }

How should it work? First, let's check what `create()` method does:

    public function create(): void
    {
        $this->db->create($this->table_name, function(Blueprint $table) {
            foreach ($this->properties as $property) {
                if ($property->explicit) {
                    $property->create($table);
                }
            }

            $table->json('_data')->nullable();
            $table->json('_overrides')->nullable();
        });

        if ($this->search->exists($this->table_name)) {
            $this->search->drop($this->table_name);
        }

        $this->search->create($this->table_name, function(SearchBlueprint $index) {
            foreach ($this->properties as $property) {
                if ($property->name === 'id') {
                    continue;
                }

                if ($property->index) {
                    $field = $property->createIndex($index);

                    if ($property->index_filterable) {
                        $field->filterable();
                    }

                    if ($property->index_sortable) {
                        $field->sortable();
                    }

                    if ($property->index_searchable) {
                        $field->searchable();
                    }

                    if ($property->index_faceted) {
                        $field->faceted();
                    }
                }
            }
        });

        foreach ($this->listeners as $listener) {
            $listener->createNotificationTables($this);
        }
    }

As you see, it does three things:

1. Creates a DB table.
2. Creates a search index.
3. Creates indexer notification tables.

The `alter()` method should compare do the same three things. In addition, it should convert existing data. 

Let's tackle these things one by one.

### Altering DB Table

First, the `alter()` method may create new columns, alter the definition of the existing columns, and drop obsolete columns.

A column is created if for new explicit properties, and for existing properties that are changed to explicit:

    $this->db->alter($this->table_name, function(Blueprint $table) {
        foreach ($this->properties as $property) {
            if (...) {
                ...
            }
        }
    });

However, I believe this simplistic approach won't work. Instead, I should create a *schema diff* - what has changed, and then process it. Back in the `Schema` class, it should work look liek as follows:

    $this->diff($current)->migrate();
    
    protected function diff(\stdClass|Schema|null $old): Migrators\Schema
    {
        return Migrators\Schema::new([
            'old' => $old,
            'new' => $this,
        ]);
    }

    // Osm\Admin\Schema\Migrators\Schema
    public function migrate(): void 
    {
        throw new NotImplemented($this);
    }
 
Schema migration should look like this:

    public function migrate(): void
    {
        foreach ($this->create_tables as $migrator) {
            $migrator->migrate();
        }

        foreach ($this->alter_tables as $migrator) {
            $migrator->migrate();
        }

        foreach ($this->drop_tables as $migrator) {
            $migrator->migrate();
        }

        foreach ($this->create_indexes as $migrator) {
            $migrator->migrate();
        }

        foreach ($this->alter_indexes as $migrator) {
            $migrator->migrate();
        }

        foreach ($this->drop_indexes as $migrator) {
            $migrator->migrate();
        }

        foreach ($this->create_notifications as $migrator) {
            $migrator->migrate();
        }

        foreach ($this->drop_notifications as $migrator) {
            $migrator->migrate();
        }

        foreach ($this->data_conversions as $migrator) {
            $migrator->migrate();
        }
    }

All the migrator arrays should be filled in by the caller, the `Schema::diff()` method, instead of passing `old` and `new` schemas:

    protected function diff(\stdClass|Schema|null $old): Migrators\Schema
    {
        $migrator = Migrators\Schema::new();

        foreach ($this->tables as $table) {
            $table->diff($migrator, $old->tables->{$table->name} ?? null);
        }

        if ($old) {
            foreach ($old->tables as $table) {
                if (isset($this->tables[$table->name])) {
                    continue;
                }

                $migrator->drop_tables[] = Migrators\Table\Drop::new([
                    'table_name' => $table->table_name,
                ]);

                $migrator->drop_indexes[] = Migrators\Index\Drop::new([
                    'index_name' => $table->table_name,
                ]);

                $migrator->drop_notifications[] = Migrators\Notification\Drop::new([
                    'table_name' => $table->table_name,
                ]);

            }
        }

        return $migrator;
    }


