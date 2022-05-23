# Implementing New Migration Approach

I must say, it's a bit disturbing to implement the same thing for the third time. Still, the goal is to get it finished, and have it sustainable, so let's continue.

After the effort, the code has become su much easier to read!

Check it out:

{{ toc }}

### meta.abstract

I must say, it's a bit disturbing to implement the same thing for the third time. Still, the goal is to get it finished, and have it sustainable, so let's continue.

After the effort, the code has become su much easier to read!

## `Property::migrate()`

The property `migrate()` method returns to its roots. It's called in various use cases:

* If it's new table, it's called in `CREATE` mode.
* If it's an existing table, it's called three times:
    * before data conversion, in `PRE_ALTER` mode;
    * for data conversion, in `CONVERT` mode;
    * after data conversion, in `POST_ALTER` mode.
* For DDL operations, a table `Blueprint` is passed as an argument.
* For SQL operations, a data conversion `Query` is passed as an argument.
* Before doing actual operation, the `migrate()` method is called one more time without passing a table or a query, and only if it returns `true`, the actual migration is executed.

The implementation:

    // Osm\Admin\Schema\Diff\Property
    public function migrate(string $mode, Blueprint $table = null,
        Query $query = null): bool
    {
        return match ($mode) {
            static::CREATE =>
                $this->migrateWithoutData($table),
            static::PRE_ALTER => empty($this->convert)
                ? $this->migrateWithoutData($table)
                : $this->beforeMigratingData($table),
            static::CONVERT =>
                !empty($this->convert) && $this->migrateData($query),
            static::POST_ALTER =>
                !empty($this->convert) && $this->afterMigratingData($table),
        };
    }

    protected function migrateWithoutData(?Blueprint $table): bool {
        $run = false;
        if ($this->new->explicit) {
            $column = $table ? $this->define($table): null;

            if ($this->create_column) {
                $run = true;
            }
            else {
                $column?->change();
            }

            foreach ($this->column as $callback) {
                $run = $run || $callback($column, $table);
            }
        }
        return $run;
    }

    protected function beforeMigratingData(?Blueprint $table): bool {
        throw new NotImplemented($this);
    }

    protected function migrateData(Query $query = null): bool {
        throw new NotImplemented($this);
    }

    protected function afterMigratingData(?Blueprint $table): bool {
        throw new NotImplemented($this);
    }

## `Property::type()`

Implementation:

    protected function type(): void {
        $this->attribute('type', function() {
            $changed = $this->change($this->old?->type !== $this->new->type);

            // This method adds nothing to the column definition. The
            // default DB type is already specified after calling the
            // `define()` method, and it may be adjusted later when
            // diffing `size` and `length` attributes.

            if (!$changed || !$this->old) {
                return;
            }

            if ($this->new->explicit && $this->old->explicit &&
                $this->letDbToConvertData())
            {
                return;
            }

            if ($this->old->explicit) {
                $this->renameOldColumn();
            }

            $this->convert();
        });
    }

This method signals that property changes using `change()` method.

If a data conversion is required (normally happens unless you convert it to `string`), it requests to rename the existing column before the data conversion and drop it afterwards.

## `Property::nullable()`

    protected function nullable(): void {
        $this->attribute('nullable', function() {
            $changed = $this->change(!$this->old ||
                $this->old->actually_nullable !== $this->new->actually_nullable);

            if ($this->new->explicit) {
                $this->column(fn(?ColumnDefinition $column) =>
                    $column?->nullable($this->new->actually_nullable)
                );
            }

            if (!$this->new->actually_nullable && $this->old?->actually_nullable) {
                $this->convert(fn(string $value) =>
                    "{$value} ?? {$this->new->default_value}");
            }
        });
    }

## `Property\Int_::size()`

    // Osm\Admin\Schema\Diff\Property\Int_
    protected function size(): void {
        $this->attribute('size', function() {
            $this->change(!$this->old ||
                $this->old->size !== $this->new->size);
            $newSize = $this->new->data_type->sizes[$this->new->size];
                
            if ($this->new->explicit) {
                $this->column(fn(?ColumnDefinition $column) =>
                    $column->type($newSize->sql_type)
                );
            }
        });
    }

When `type`, `size` or `unsigned` changes, data conversion may be needed. I factored this logic into a separated method that is called after all these attributes are diffed:

    // Osm\Admin\Schema\Diff\Property\Int_
    protected function checkRange(): void {
        if (!$this->old) {
            return;
        }
        
        if (isset($this->change['type']) || 
            isset($this->change['size']) && $this->becomingSmaller() ||
            isset($this->change['unsigned'])) 
        {
            $newSize = $this->new->data_type->sizes[$this->new->size];

            if ($this->new->unsigned) {
                $min = 0;
                $max = $newSize->unsigned_max;
            }
            else {
                $min = $newSize->min;
                $max = $newSize->max;
            }
            
            $this->convert(fn(string $value) =>
                "{$value} > $max ? $max : ({$value} < $min ? $min : {$value})");
        }
    }

    protected function becomingSmaller(): bool {
        $sizes = $this->new->data_type->sizes;
        
        return
            array_search($this->old->size, array_keys($sizes)) >
            array_search($this->new->size, array_keys($sizes));
    }

## `Property\Int_::unsigned()`

    protected function unsigned(): void {
        $this->attribute('unsigned', function() {
            $this->change(!$this->old ||
                $this->old->actually_unsigned !== $this->new->actually_unsigned);

            if ($this->new->explicit) {
                $this->column(fn(?ColumnDefinition $column) =>
                    $this->new->actually_unsigned
                        ? $column->unsigned()
                        : $column
                );
            }
        });
    }

## `Property\Int_::autoIncrement()`

    protected function autoIncrement(): void {
        $this->attribute('auto_increment', function() {
            if (isset($this->old->auto_increment) &&
                $this->old->auto_increment !== $this->new->auto_increment)
            {
                throw new InvalidChange(__("'#[AutoIncrement]' attribute of the ':table.:property' can't be changed", [
                    'property' => $this->new->name,
                    'table' => $this->new->parent->table_name,
                ]));
            }

            if ($this->new->explicit && $this->new->auto_increment) {
                $this->column(fn(?ColumnDefinition $column) =>
                    $column->autoIncrement()
                );
            }
        });
    }

## `String_::$size` And `String_::$max_length`

String-specific property attributes are handled in a similar fashion as `Int_::$size` does:

    // Osm\Admin\Schema\Diff\Property\String_
    protected function size(): void {
        $this->attribute('size', function() {
            $this->change(!$this->old ||
                $this->old->type !== $this->new->type ||
                $this->old->size !== $this->new->size);
            $newSize = $this->new->data_type->sizes[$this->new->size];

            if ($this->new->explicit) {
                $this->column(fn(?ColumnDefinition $column) =>
                    $column?->type($newSize->sql_type)
                );
            }
        });
    }

    /** @noinspection PhpUndefinedMethodInspection */
    protected function length(): void {
        $this->attribute('max_length', function() {
            $this->change(!$this->old ||
                $this->old->type !== $this->new->type ||
                ($this->old->max_length ?? null) !== $this->new->max_length);

            if ($this->new->explicit) {
                $this->column(fn(?ColumnDefinition $column) =>
                    $this->new->max_length
                        ? $column
                            ?->type('string')
                            ?->length($this->new->max_length)
                        : $column
                );
            }
        });
    }

    protected function truncate(): void {
        if (!$this->old) {
            return;
        }

        if (isset($this->change['type']) ||
            (
                isset($this->change['size']) ||
                isset($this->change['max_length'])
            ) && $this->becomingShorter())
        {
            $maxLength = $this->maxLength($this->new);

            $this->convert(fn(string $value) =>
                "LENGTH({$value} ?? '') > $maxLength ? " .
                "LEFT({$value}, $maxLength) : {$value}");
        }
    }

    protected function maxLength(
        StringPropertyObject|\stdClass|null $property): int
    {
        if (!$property) {
            return 0;
        }

        if ($property->max_length ?? null) {
            return $property->max_length;
        }

        return $this->new->data_type->sizes[$property->size]->max_length;
    }

    protected function becomingShorter(): bool {
        return $this->maxLength($this->old) > $this->maxLength($this->new);
    }

## `Property::migrate*()` Methods

After hitting the first migration test that requires the data conversion, I've got to implement what happens before/after/around the conversion:

    protected function migrateWithoutData(?Blueprint $table): bool {
        return $this->migrateColumn($table);
    }

    protected function beforeMigratingData(?Blueprint $table): bool {
        $run = false;

        if ($this->rename_old_column) {
            $this->migrateColumn($table);
            $table?->renameColumn($this->old->name, "old__{$this->old->name}");
            $run = true;
        }

        return $run;
    }

    protected function migrateData(Query $query = null): bool {
        if (!count($this->convert)) {
            return false;
        }

        $propertyName = $this->rename_old_column
            ? "old__{$this->old->name}":
            $this->old->name;

        $value = $this->old->explicit
            ? "COLUMN('{$propertyName}', '{$this->old->type}')"
            : "DATA('{$propertyName}', '{$this->old->type}')";

        foreach ($this->convert as $callback) {
            if ($callback === true) {
                continue;
            }

            $value = $callback($value);
        }

        $query?->select("{$value} AS {$this->new->name}");
        return true;
    }

    protected function afterMigratingData(?Blueprint $table): bool {
        if ($this->rename_old_column) {
            $table?->dropColumn("old__{$this->old->name}");
            $run = true;
        }
        else {
            $run = $this->migrateColumn($table);
        }

        return $run;
    }

    protected function migrateColumn(?Blueprint $table): bool {
        $run = false;

        if ($this->new->explicit) {
            $column = $table ? $this->define($table): null;

            if ($this->create_column) {
                $run = true;
            }
            else {
                $column?->change();
                $run = count($this->change) > 0;
            }

            foreach ($this->column as $callback) {
                $callback($column, $table);
            }
        }
        return $run;
    }
 