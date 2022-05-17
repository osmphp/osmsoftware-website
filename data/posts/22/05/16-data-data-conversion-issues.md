# Data Conversion Issues

Last time, I pushed through `string` property migrations and created a migration log.

Let's continue solving data conversion issues.

{{ toc }}

### meta.abstract

Last time, I pushed through `string` property migrations and created a migration log.

Today, I continued solving data conversion issues.

## Undefined Method `Diff\Property\Int_::convert()`

Indeed, I moved data conversion logic to new `Migration\*` classes, and didn't update the calling code. 

Here it is updated:

    // Osm\Admin\Schema\Diff\Table
    protected function get_requires_convert(): bool {
        foreach ($this->properties as $property) {
            if ($property->requiresMigration(Property::CONVERT)) {
                return true;
            }
        }

        return false;
    }

    protected function convert(): void {
        $query = Query::new(['table' => $this->new]);

        if ($this->requires_convert) {
            $this->log(__("Converting ':table' table", [
                'table' => $this->new->table_name,
            ]));

            foreach ($this->properties as $property) {
                $property->convert($query);
            }

            $query->bulkUpdate();
        }
    }

    // Osm\Admin\Schema\Diff\Property
    public function convert(Query $query): void {
        if (!$this->requiresMigration(static::CONVERT)) {
            return;
        }

        $new = "{$this->migration_class_name}::new";

        $new([
            'property' => $this,
            'mode' => static::CONVERT,
            'query' => $query,
        ])->migrate();
    }

## `Migration::$default_value`

The ``Migration::$default_value`` returns a value that should be assigned to a property if old value can't be converted, for example if a non-numeric string is converted to int.  

It returns `NULL` for nullable properties, and a falsy value (`0` for `int`, `0.0` for `float`, `false` for `bool`, `'-'` for `string`):

    // Osm\Admin\Schema\Diff\Migration\String_
    protected function get_default_value(): string {
        return $this->property->new->actually_nullable
            ? "NULL"
            : "'-'";
    }

    // Osm\Admin\Schema\Diff\Migration\Int_
    protected function get_default_value(): string {
        return $this->property->new->actually_nullable
            ? "NULL"
            : "0";
    }

## `test_make_explicit_property_non_nullable` Doesn't Invoke Data Conversion

When a property becomes non-nullable, `NULL` should be converted to the default value.

However, no data conversion occurs. Why?

It turns out, the nullability handling didn't call the `run()` method, and the migration didn't think any conversion is needed.

The fix:

    // Osm\Admin\Schema\Diff\Migration
    protected function becomeNonNullable(): void {
        switch ($this->mode) {
            ...
            case Property::CONVERT:
                $this->new_value = "{$this->new_value} ?? $this->default_value";
                $this->run('nullable');
                break;
            ...
        }
    }
   
## `Add a select expression to the query` Error

The next thing that changed is that migration classes only prepare formulas for the data conversion query, but don't add them to the query.

Fix:

    // Osm\Admin\Schema\Diff\Property
    public function convert(Query $query): void {
        if (!$this->requiresMigration(static::CONVERT)) {
            return;
        }

        $new = "{$this->migration_class_name}::new";

        /* @var Migration $migration */
        $migration = $new([
            'property' => $this,
            'mode' => static::CONVERT,
            'query' => $query,
        ]);

        $migration->migrate();

        $formula = str_replace('{{old_value}}', $migration->old_value,
            $migration->new_value);
        $query->select("{$formula} AS {$this->new->name}");
    }

