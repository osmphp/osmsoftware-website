# Changing Property Type

After enumerating what kind of changes can happen to a property, I started implementing the most hard one - changing property type.

{{ toc }}

### meta.abstract

After enumerating what kind of changes can happen to a property, I started implementing the most hard one - changing property type.

## Property Changes

So far, I've implemented migrations that from both from scratch and incrementally for implicit and explicit `int` and `string` properties, both nullable and non-nullable.

You can switch property nullability however you want, and the existing data is converted according to certain rules that ensure minimum data loss, if any at all.

The goal is to allow you changing anything in property definitions however you want, and be sure that the existing data is converted to new format, or, in rare cases, showing you a clear error explaining what you've done wrong and how to fix it.

But what does it mean - changing anything? Here is a list of possible changes, listed by complexity and, hence, priority:

* Change property type.
* Make implicit property explicit, and vice versa.
* Change type-specific attributes of explicit columns:
   * `#[Unsigned]`
   * `#[Length]`
   * `#[Tiny/Small/Medium/Long]`
* Rename a property.
* Change property nullability.
* Change property formula.

**Later**. This is not a comprehensive list of all possible changes, it will be extended if needed.

**Later**. Several changes may happen at once, and additional tests are needed to check composite changes, for example, making property implicit, non-nullable, changing its type and renaming it in one go.

## Changing Property Type

True, the logic of property type conversion may differ depending on the "old" and "new" type.

**Later**. It also depends on whether a property is implicit or explicit. For now, let's only explore explicit properties, that is, the ones having dedicated table columns.

Let's imagine a couple of different type conversions, it will help to see some common patterns.

### Converting `int` To `string`

Let's say we have a `color` column that holds a product quantity as an unsigned integer. MySql is capable to convert existing data on its own, so it enough to just change the type of the column, and that's it.

### Converting `string` To `int`

If you convert it back to `int`, how should it go, step-by-step:

1. **Pre-alter**. Rename the old `color` column to `old__color`. Create new `color` column of the  `int` type.
2. **Convert**. Update existing rows using the `color = STRING_TO_INT(COLUMN('old__color'), 0)`. Here:
   * `STRING_TO_INT()` function should check if a given value can be converted to the specified internally call MySql [`CONVERT()` function](https://dev.mysql.com/doc/refman/8.0/en/cast-functions.html#function_convert). When converting anon-numeric value, it uses the saecond argument as a sensible default.
   * `COLUMN()` function should retrieve value from the specified column without checking for any schema information. It's needed as the `old__color` column is temporary, and there is no such property defined.
3. **Post-alter**. Delete `old__color` column.

### Schema Fixtures

Let's add an `int` property in eth fourth schema version, convert it to `string` in the fifth schema version, and convert it back to `int` in the sixth schema version:

    // Osm\Admin\Samples\Migrations\String_\V004\Product

    /**
     * @property string $description #[Explicit]
     * @property ?int $color #[Explicit]
     *
     * @uses Explicit
     */
    #[Fixture]
    class Product extends Record
    {
    
    }

    // Osm\Admin\Samples\Migrations\String_\V006\Product

    /**
     * @property string $description #[Explicit]
     * @property ?string $color #[Explicit]
     *
     * @uses Explicit
     */
    #[Fixture]
    class Product extends Record
    {
    
    }

    // Osm\Admin\Samples\Migrations\String_\V006\Product

    /**
     * @property string $description #[Explicit]
     * @property ?int $color #[Explicit]
     *
     * @uses Explicit
     */
    #[Fixture]
    class Product extends Record
    {
    
    }

### Failing Tests

Two tests should verify that an `int` value can be converted to `string` and back to `int`, and that a non-numeric value is handled, too:

    public function test_conversion_from_int() {
        // GIVEN database with `V4` schema and some data
        $this->loadSchema(Product::class, 4);
        $this->app->schema->migrate();

        $id = query(Product::class)
            ->where("title = 'Lorem ipsum'")
            ->value("id");
        
        query(Product::class)
            ->where("id = {$id}")
            ->update(['color' => 0xFFFFFF]);    

        // WHEN you run `V5` migration
        $this->loadSchema(Product::class, 5);
        $this->app->schema->migrate();

        // THEN `color` is converted from int
        $this->assertSame((string)0xFFFFFF, 
            $this->app->db->table('products')
                ->where('id', $id)
                ->value('color'));
    }

    public function test_conversion_to_int() {
        // GIVEN database with `V5` schema and some data
        $id1 = query(Product::class)
            ->where("title = 'Lorem ipsum'")
            ->value("id");
        $id2 = ui_query(Product::class)->insert((object)[
            'title' => 'Invalid color',
            'color' => 'black', // non-numeric
        ]);

        // WHEN you run `V6` migration
        $this->loadSchema(Product::class, 6);
        $this->app->schema->migrate();

        // THEN `color` is converted to int
        $this->assertSame(0xFFFFFF, $this->app->db->table('products')
            ->where('id', $id1)
            ->value('color'));
        $this->assertSame(0, $this->app->db->table('products')
            ->where('id', $id2)
            ->value('color'));
    }

Neither test works as expected, let's solve their issues one by one.

### Detecting Type Conversion

In the `test_conversion_from_int()`, type doesn't change, and the assertion failure indicates that:

    Failed asserting that 16777215 is identical to '16777215'.  

Why? The reason is, the migration code doesn't detect this change. Let's change that:

    // Osm\Admin\Schema\Diff\Property\String_
    public function migrate(string $mode, Blueprint $table = null): bool {
        ...
        $run = $this->type($mode, $table) || $run;
        ...
    }

    // Osm\Admin\Schema\Diff\Property
    protected function type(string $mode, ?Blueprint $table): bool
    {
        if ($mode == static::CREATE) {
            return true;
        }

        if ($this->old->type === $this->new->type) {
            return false;
        }

        throw new NotImplemented($this);
    }

Now it stops at the first property type change.

### By Default, Trust MySql To Convert The Data

All custom DDL conversion will happen in overridable `fromType` method. By default, this method tells Osm Admin to just convert the column type and data by MySql means: 

    // Osm\Admin\Schema\Diff\Property
    protected function type(string $mode, ?Blueprint $table): bool
    {
        ...
        $this->fromType($mode, $table);

        return true;
    }

    protected function fromType(string $mode, ?Blueprint $table): bool {
        // by default, trust MySql to do all the data conversion
        // implicitly during DDL type change
        return false;
    }

### Need For Migration Log

I noticed that two strange things happen in `temp/{app_name}/logs/db_{date}.log`:

1. When migrating schema from version 4 to 5 too many DDL statements take place:

        [2022-05-09T08:36:11.626401+00:00] db.INFO: ALTER TABLE products 
            CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, 
            CHANGE description description TEXT CHARACTER SET utf8mb4 NOT NULL 
                COLLATE `utf8mb4_unicode_ci`, 
            CHANGE color color TEXT DEFAULT NULL {"bindings":[],"time":43.78} []
        [2022-05-09T08:36:11.626583+00:00] db.INFO: ALTER TABLE products 
            CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL,  
            CHANGE description description TEXT CHARACTER SET utf8mb4 NOT NULL  
                COLLATE `utf8mb4_unicode_ci`,  
            CHANGE color color TEXT DEFAULT NULL {"bindings":[],"time":43.78} []
        [2022-05-09T08:36:21.294708+00:00] db.INFO: ALTER TABLE products  
            CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL,  
            CHANGE description description TEXT CHARACTER SET utf8mb4 NOT NULL  
                COLLATE `utf8mb4_unicode_ci`,  
            CHANGE color color TEXT CHARACTER SET utf8mb4 DEFAULT NULL  
                COLLATE `utf8mb4_unicode_ci` {"bindings":[],"time":21.91} []
        [2022-05-09T08:36:21.295033+00:00] db.INFO: ALTER TABLE products  
            CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL,  
            CHANGE description description TEXT CHARACTER SET utf8mb4 NOT NULL  
                COLLATE `utf8mb4_unicode_ci`,  
            CHANGE color color TEXT CHARACTER SET utf8mb4 DEFAULT NULL  
                COLLATE `utf8mb4_unicode_ci` {"bindings":[],"time":21.91} []

2. When migrating schema from version 5 to 6, no DDL statements are issued at all.

Migration engine is non-trivial, and similar strange things may happen in the future. Hence, it's worth taking time and provide an efficient tool for analyzing such things - a migration log.  

As the [logging guide](../04/18-data-logging.md) suggests, every log should answer one question, and answer it well. The migration log answers the following question:

*What changes are made to the database, and why?*

**Later**. It should be possible to output the migration detailed log to the console instead of the log file.  

### Migration Log Example

Here is what I want to see in the log for the initial migration:

    Creating 'products' table:
        Creating 'id' property:
            Explicit: true
            Type: int
            Unsigned: true
            Nullable: false
            ...
        Creating 'title' property:
            Explicit: true
            Type: string
            Nullable: false
        ...
    ...

It should be a bit different when altering existing tables/properties: 
            
    Altering 'products' table:
        Creating 'description' property:
            Explicit: true
            Type: string
            Nullable: true
        Altering 'color' property:
            Changing type: int -> string
            Changing nullable: false -> true  
            
### Logging Configuration

Migration log is configured in the same way as other [logs](https://osm.software/docs/framework/0.15/other-features/logging.html). There is a switch in the `settings.php` file that defaults to the value of the `LOG_MIGRATIONS` environment variable.

Here is how it's implemented: 

    // Osm\Admin\Schema\Diff\Table
        
    /**
     * ...
     * @property Logger $log
     */
    class Table extends Diff
    {
        ...
        protected function log(string $message): void {
            $this->log->notice($message);
        }
    
        protected function get_log(): Logger {
            global $osm_app; /* @var App $osm_app */
    
            return $osm_app->logs->migrations;
        }
    }
    
    // Osm\Admin\Schema\Traits\LogsTrait
    ...
    /**
     * @property Logger $migrations
     */
    #[UseIn(Logs::class)]
    trait LogsTrait
    {
        protected function get_migrations(): Logger {
            global $osm_app; /* @var App $osm_app */
    
            $logger = new Logger('migrations');
            if ($osm_app->settings->logs?->migrations ?? false) {
                $logger->pushHandler(new RotatingFileHandler(
                    "{$osm_app->paths->temp}/logs/migrations.log"));
            }
    
            return $logger;
        }
    } 
    
    // Osm\Admin\Schema\Traits\LogSettingsTrait
    ...
    /**
     * @property ?bool $migrations
     */
    #[UseIn(LogSettings::class)]
    trait LogSettingsTrait
    {
    
    }
    
    // src/Schema/settings.php
    ...
    return (object)[
        ...
        /* @see \Osm\Framework\Logs\Hints\LogSettings */
        'logs' => (object)[
            'migrations' => (bool)($_ENV['LOG_MIGRATIONS'] ?? false),
        ],
    ];

    // .env.Osm_Admin_Samples
    ...    
    LOG_MIGRATIONS=true

### Implicit Properties Are Excluded    

After listing created/altered properties, I noticed that implicit properties (`title`) are missing:

    Migrating 'Osm\Admin\Samples\Migrations\String_\V001\' schema fixture 
    Creating 'products' table 
        Creating 'id' property 
    --------------------------------------------- 
    Migrating 'Osm\Admin\Samples\Migrations\String_\V002\' schema fixture 
    Pre-altering 'products' table 
        Altering 'id' property 
        Creating 'description' property 
    --------------------------------------------- 
    Migrating 'Osm\Admin\Samples\Migrations\String_\V003\' schema fixture 
    Converting 'products' table 
    Post-altering 'products' table 
        Altering 'id' property 
        Altering 'description' property 
    --------------------------------------------- 
    Migrating 'Osm\Admin\Samples\Migrations\String_\V004\' schema fixture 
    Pre-altering 'products' table 
        Altering 'id' property 
        Altering 'description' property 
        Creating 'color' property 
    --------------------------------------------- 
    Migrating 'Osm\Admin\Samples\Migrations\String_\V005\' schema fixture 
    Pre-altering 'products' table 
        Altering 'id' property 
        Altering 'description' property 
        Altering 'color' property 
    Post-altering 'products' table 
        Altering 'id' property 
        Altering 'description' property 
        Altering 'color' property 
    --------------------------------------------- 
    Migrating 'Osm\Admin\Samples\Migrations\String_\V006\' schema fixture 

It happens because implicit properties are indeed excluded from the migration algorithm:

    // Osm\Admin\Schema\Diff\Table::create()
    foreach ($this->properties as $property) {
        if (!$property->new->explicit) {
            continue;
        }

        $property->migrate(Property::CREATE, $table);
    }

    // Osm\Admin\Schema\Diff\Table::preAlter()
    foreach ($this->properties as $property) {
        if (!$property->new->explicit) {
            continue;
        }

        $property->migrate($property->old?->explicit
            ? Property::PRE_ALTER
            : Property::CREATE, $table);
    }

    // Osm\Admin\Schema\Diff\Table::postAlter()
    foreach ($this->properties as $property) {
        if (!$property->new->explicit) {
            continue;
        }

        if ($property->old?->explicit) {
            $property->migrate(Property::POST_ALTER, $table);
        }
    }
    
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


It prevents having the full picture in the log. In the long term, it will cause trouble when changing property explicitness.

Let's fix that.

### Handling Implicit Properties

First, let's make the `Property::migrate()` method responsible for handling property explicitness. This way, the calling code calls the `Property::migrate()` for *every* property, and passes the migration phase *as is*:

    // Osm\Admin\Schema\Diff\Table::create()
    foreach ($this->properties as $property) {
        $property->migrate(Property::CREATE, $table);
    }

    // Osm\Admin\Schema\Diff\Table::preAlter()
    foreach ($this->properties as $property) {
        $property->migrate(Property::PRE_ALTER, $table);
    }

    // Osm\Admin\Schema\Diff\Table::convert()
    foreach ($this->properties as $property) {
        $property->convert($query);
    }

    // Osm\Admin\Schema\Diff\Table::postAlter()
    foreach ($this->properties as $property) {
        $property->migrate(Property::POST_ALTER, $table);
    }

    protected function get_requires_pre_alter(): bool {
        foreach ($this->properties as $property) {
            if ($property->migrate(Property::PRE_ALTER)) {
                return true;
            }
        }

        return false;
    }

    protected function get_requires_post_alter(): bool {
        foreach ($this->properties as $property) {
            if ($property->migrate(Property::POST_ALTER)) {
                return true;
            }
        }

        return false;
    }

To be continued.