# Adding Explicit Properties. Data Conversions

This time, I tackled adding an explicit property to an existing table, and then making it non-nullable. 

It raised the need for data conversions - additional handling of the existing data - or otherwise, the database engine fails, or the data becomes invalid.

And it made me split migrations in two parts - the one that runs before the data conversion, and the other one that runs afterwards.

Here is how it went:

{{ toc }}

### meta.abstract

This time, I tackled adding an explicit property to an existing table, and then making it non-nullable.

It raised the need for data conversions - additional handling of the existing data - or otherwise, the database engine fails, or the data becomes invalid.

And it made me split migrations in two parts - the one that runs before the data conversion, and the other one that runs afterwards.

## Adding Explicit Properties To Existing Table

Yesterday, the first migration test passed. It creates a table with standard properties in the empty database without previous schema.

The second tests adds a property to an existing table, and as such it has to actually compare things with the existing table. Let's make it work.

### `requires_alter`

Currently, it stops on unimplemented getter:

    // Osm\Admin\Schema\Diff\Table
    protected function get_requires_alter(): bool {
        throw new NotImplemented($this);
    }

The `requires_alter` property should return `true` if any property diff contributes changes to the database table structure, and `false` otherwise:

    protected function get_requires_alter(): bool {
        foreach ($this->properties as $property) {
            if ($property->requires_alter) {
                return true;
            }
        } 
        
        return false;
    }

One case when a property `requires_alter` is when it's new and explicit:

    protected function get_requires_alter(): bool {
        if (!$this->old) {
            return $this->new->explicit;
        }
        
        return false;
    }

**Later**. I'll add more cases later.

Let's continue.

### Table 'zi1__products__inserts' Already Exists

The second migration run tries to create notification tables once again. Why? Because notification table migration currently creates a table no matter what. 

Let's make it more intelligent:

    // Osm\Admin\Schema\Diff\NotificationTable
    public function migrate(): void {
        if (!$this->changed) {
            return;
        }
        
        if ($this->old) {
            $this->drop();
        }
        
        $this->create();
    }

    protected function get_changed(): bool {
        if (!$this->old) {
            return true;
        }
        
        if ($this->old->table_name !== $this->new->table_name) {
            return true;
        }

        if ($this->old->indexer_id !== $this->new->indexer_id) {
            return true;
        }

        if ($this->old->suffix !== $this->new->suffix) {
            return true;
        }

        if ($this->old->cascade !== $this->new->cascade) {
            return true;
        }

        return false;
    }

    protected function get_old_db_table_name(): string {
        return "zi{$this->old->indexer_id}__{$this->old->table->table_name}" . 
            "__{$this->old->suffix}";
    }

    protected function drop(): void {
        $this->db->drop($this->old_db_table_name);
    }

Yay! It works!

## Need For Data Conversion

Actually, I've been lucky that the schema change did not require converting existing data to the new schema. Often, it does:

* implicit values should be assigned sensible defaults;
* values should be converted to new data types;
* former implicit values should be moved to new explicit columns, and vice versa;
* nullability and type changes and other validation rule changes should trigger validation;
* regular indexer should fill in new computed properties;
* and more.

## Making Explicit Property Non-Nullable

Let's create a test that makes the `description` property non-nullable, and the migration fails.

### Test

    public function test_make_explicit_property_non_nullable() {
        // GIVEN database with `V2` schema and some data

        // WHEN you run `V3` migration
        $this->loadSchema(Product::class, 3);
        $this->app->schema->migrate();
    }

**Later**. I'll add the asserts later.

### Schema Fixture

In the data class, only the `?` is removed in the property definition:

    /**
     * @property string $description #[Explicit]
     *
     * @uses Explicit
     */
    #[Fixture]
    class Product extends Record
    {
    
    } 

### Detecting Nullability Changes

The test runs OK, and it doesn't alter the `products` table. Why?

The `requires_alter` doesn't detect nullability changes, that's why.

    // Osm\Admin\Schema\Diff\Property
    protected function get_requires_alter(): bool {
        if (!$this->old) {
            return $this->new->explicit;
        }

        return false;
    }

Let's fix that:

    protected function get_requires_alter(): bool {
        if (!$this->new->explicit) {
            return false;
        }

        if (!$this->old?->explicit) {
            // create a column for new explicit property, or for an existing
            // property that were previously implicit
            return true;
        }

        // from now on, we know that the property already exists, and that the
        // property has an explicit column

        if ($this->old->nullable !== $this->new->nullable) {
            // alter column nullability
            return true;
        }

        return false;
    }

**Later**. A better place for this logic is the `Property::diff()` method.

### `Property::alter()`

Currently, there is no logic handling explicit column changes:

    protected function alter(Blueprint $table): void {
        throw new NotImplemented($this);
    }

Laravel provides a [neat syntax for altering existing columns](https://laravel.com/docs/9.x/migrations#updating-column-attributes), very similar to creating new columns. So, there is no need for `alter()` method at all. 

Instead, I updated the `migrate()` method:

    public function migrate(Blueprint $table): void {
        if (!$this->new->explicit) {
            return;
        }

        $column = $this->create($table);
        if ($this->alter) {
            $column->change();
        }
    }

### Null Value Not Allowed

Yes! The test fails as it should. There is a record having `NULL` value in the `description` column, and the test tried to make this column non-nullable.

Before going further, let's stop a bit on what's the best possible handling of this situation.

Imagine you deploy such a change to production. Is it really the best for you if says that "no, your data doesn't fit into the new schema"? And now you have to restore production database, and think of a fix. Sure it's not. 

Deployment should never fail, and it means, migrations should never fail, either. 

It means, that Osm Admin should convert all the existing data to new schema no matter what. In many cases, it's possible without data loss. When data loss is possible, you should see a warning. After migration is over, you can check review the data based on these warnings, and if unacceptable data loss happened, you can restore the system to last good version.

In this specific case, before converting a `NULL` column to non-null, Osm Admin should replace NULLs with empty strings, or in general, with falsy values (`''`, `0`, `false`, and other).

When converting back, it should convert empty strings to NULLs again.

On the other hand, non-nullable strings don't allow empty strings as user input, so basically, this would introduce a hack to the whole data validation process. It's even more important for `#[Select]` properties - an empty string is not even a valid option, so your application logic may have some hard time later dealing with an invalid value that just should be there.

It's not right. It means that migrations should still fail if it detects an invalid value. To resolve that, provide a `#[Convert]` formula for a property that always returns a valid value. It also means that for non-nullable strings `'-'` is a better default value than `''`, as it a valid value for a required field.

### Table Migration Steps

As you see, a table migration is more than just changes to table structure. It should be done in several steps:

1. **Pre-migrate**. Create/alter table columns except changes that may result in a database exception (`NULL` in a non-nullable column, non-unique value in a unique column, non-existing ID in a reference column).
2. **Convert**. Convert values in changed columns using `#[Computed]`, `#[Overridable]`, `#[Convert]` formula, or a falsy value (`'-'` for strings) if non-of them defined.
3. **Validate**. Run validation and fail if it fails.
4. **Post-migrate**. Apply the creation/alteration rules that weren't applied in step 1.   

**Later**. Validation is not implemented yet.

**Later**. Conversion will only use falsy values. Formula attributes will be implemented later.

In case of this exact migration - making `description` column non-NULL, it should work as follows:

1. **Pre-migrate**. Does nothing - the only table structure change is delayed until the post-migrate step.
2. **Convert**. Fills in the `description` column in the existing records with `'-'` value.
3. **Validate**. Does nothing - validation is not implemented yet.
4. **Post-migrate**. Makes the `description` column non-NULL.   

