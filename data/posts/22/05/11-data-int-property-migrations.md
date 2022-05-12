# `int` Property Migrations

Yesterday, I finished writing `int` property migrations. True, testing it is still a todo. 

The major part of the code (type change, nullability and other attribute handling) will be reused in other property types.

Contents:


{{ toc }}

### meta.abstract

Yesterday, I finished writing `int` property migrations. True, testing it is still a todo.

The major part of the code (type change, nullability and other attribute handling) will be reused in other property types.

## Generic Attributes

### `Migration::type()`

By default, if explicit property type changes, Osm Admin just changes the DDL type and trusts MySql to do all the data conversion implicitly during DDL type change. It can be overridden, for example, to provide custom logic when converting `string` to `int`, by overriding the following method:

    protected function changeTypeByDbMeans(): bool {
        throw new NotImplemented($this);
    }

In other cases, Osm Admin renames the old column and creates the new column in the pre-alter phase, converts existing values using the `CONVERT()` function, and drops the old column in the post-alter phase.

Here is the code:

    protected function type(): void {
        if (!$this->property->old ||
            $this->property->old->type === $this->property->new->type)
        {
            return;
        }

        if ($this->property->new->explicit && $this->property->old->explicit &&
            $this->changeTypeByDbMeans())
        {
            $this->runCreateOrPreAlterMigration();
        }
        else {
            $this->renameOldColumn();
            $this->convertType();
        }
    }

    protected function runCreateOrPreAlterMigration(): void {
        if ($this->mode == Property::CREATE ||
            $this->mode == Property::PRE_ALTER)
        {
            $this->run = true;
        }
    }

    protected function renameOldColumn(): void {
        if (!$this->property->new->explicit || !$this->property->old->explicit) {
            return;
        }

        if ($this->rename_old_column) {
            return;
        }

        $this->rename_old_column = true;

        switch ($this->mode) {
            case Property::CREATE:
                $this->cantAlterPropertyOnCreate();
            case Property::PRE_ALTER:
                if ($this->table) {
                    $this->table->renameColumn($this->property->old->name,
                        "old__{$this->property->old->name}");
                }
                $this->run = true;
                break;
            case Property::CONVERT:
                $this->old_value = $this->value(
                    "old__{$this->property->old->name}");
                $this->run = true;
                break;
            case Property::POST_ALTER:
                if ($this->table) {
                    $this->table->dropColumn(
                        "old__{$this->property->old->name}");
                }
                break;
        }
    }

    protected function convertType(): void {
        if ($this->mode == Property::CONVERT) {
            $this->new_value = "CONVERT({$this->new_value}, " .
                "'{$this->property->old->type}', '" .
                "{$this->property->new->type}', $this->default_value)";
        }
    }
 
**Notice**. `runCreateOrPreAlterMigration()` and `renameOldColumn()` are written in a way that you can call them multiple times while handling various property attributes.

### `Migration::nullable()`

I've already implemented handling of the [nullability changes before](../04/30-data-alter-table-non-null-data-conversion-query-test-suite.md). Here is how it looks in the new code structure:

    protected function nullable(): void {
        if (!$this->property->new->actually_nullable &&
            $this->property->old?->actually_nullable)
        {
            $this->becomeNonNullable();
        }
        elseif ($this->mode == Property::CREATE ||
                $this->mode == Property::PRE_ALTER)
        {
            if ($this->column) {
                $this->column->nullable($this->property->new->actually_nullable);
            }
            $this->run = true;
        }
    }

    protected function becomeNonNullable(): void {
        switch ($this->mode) {
            case Property::CREATE:
            case Property::PRE_ALTER:
                break;
            case Property::CONVERT:
                $this->new_value = "{$this->new_value} ?? $this->default_value";
                break;    
            case Property::POST_ALTER:
                if ($this->column) {
                    $this->column->nullable(false);
                }
                $this->run = true;
                break;
        }
    }

## `int` Attributes

### `Migration\Int_::size()`

If `INT` column becomes `TINYINT`, all values outside allowed value range (`0..255` if unsigned, `-128..127` if signed) should be changed to the closest allowed value:

    $min = 0;
    $max = 255;
    $this->new_value = "IF({$this->new_value} > $max, $max, " . 
        "IF({$this->new_value} < $min, $min, {$this->new_value}))";

On the contrary, if value range increases, no range check is necessary.

It should be possible to invoke range check multiple times as it's needed in other cases, for example, if `unsigned` changes, or if property type is changed to `int`.

If conversion is needed, pre-alter phase should not change the column definition.        

Here is the implementation:

    protected function size(): void {
        if (!$this->property->old) {
            $this->preSize();
            return;
        }

        if ($this->property->old->size === $this->property->new->size) {
            return;
        }

        if ($this->becomingSmaller()) {
            $this->checkRange();
            $this->postSize();
        }
        else {
            $this->preSize();
        }
    }

    protected function preSize(): void {
        if ($this->mode == Property::CREATE ||
            $this->mode == Property::PRE_ALTER)
        {
            $this->setSize();
        }
    }

    protected function postSize(): void {
        if ($this->mode == Property::POST_ALTER) {
            $this->setSize();
        }
    }

    protected function setSize(): void {
        if ($this->column) {
            $this->column->type(
                $this->sizes[$this->property->new->size]->sql_type);
            $this->run = true;
        }
    }

    protected function becomingSmaller(): bool {
        return
            array_search($this->property->old->size, array_keys($this->sizes)) >
            array_search($this->property->new->size, array_keys($this->sizes));
    }

    protected function checkRange(): void {
        if ($this->check_range) {
            return;
        }

        $this->check_range = true;

        if ($this->mode === Property::CONVERT) {
            if ($this->property->new->unsigned) {
                $min = 0;
                $max = $this->sizes[$this->property->new->size]->unsigned_max;
            }
            else {
                $min = $this->sizes[$this->property->new->size]->min;
                $max = $this->sizes[$this->property->new->size]->max;
            }
            
            $this->new_value = "IF({$this->new_value} > $max, $max, " .
                "IF({$this->new_value} < $min, $min, {$this->new_value}))";
            $this->run = true;    
        }
    }
    
    ...
    /**
     * @var IntSize[]
     */
    protected array $sizes = [
        PropertyObject::TINY => (object)[
            'sql_type' => 'tinyInteger',
            'min' => -0x80,
            'max' => 0x7F,
            'unsigned_max' => 0xFF,
        ],
        PropertyObject::SMALL => (object)[
            'sql_type' => 'smallInteger',
            'min' => -0x8000,
            'max' => 0x7FFF,
            'unsigned_max' => 0xFFFF,
        ],
        PropertyObject::MEDIUM => (object)[
            'sql_type' => 'integer',
            'min' => -0x80000000,
            'max' => 0x7FFFFFFF,
            'unsigned_max' => 0xFFFFFFFF,
        ],
        PropertyObject::LONG => (object)[
            'sql_type' => 'bigInteger',
            'min' => -0x8000000000000000,
            'max' => 0x7FFFFFFFFFFFFFFF,
            'unsigned_max' => 0xFFFFFFFFFFFFFFFF,
        ],
    ];

    public bool $check_range = false;
    
### `Migration\Int_::unsigned()`

If a property becomes `unsigned`, or not `unsigned`, the existing values should be range-checked just as when [changing size](#migrationint_size). 

    protected function unsigned(): void {
        if (!$this->property->old) {
            $this->preUnsigned();
            return;
        }

        if ($this->property->old->actually_unsigned === 
            $this->property->new->actually_unsigned) 
        {
            return;
        }

        $this->checkRange();
        $this->postUnsigned();
    }

    protected function preUnsigned(): void {
        if ($this->mode == Property::CREATE ||
            $this->mode == Property::PRE_ALTER)
        {
            $this->setUnsigned();
        }
    }

    protected function postUnsigned(): void {
        if ($this->mode == Property::POST_ALTER) {
            $this->setUnsigned();
        }
    }

    protected function setUnsigned(): void {
        if ($this->column) {
            if ($this->property->new->actually_unsigned) {
                $this->column->unsigned();
            }
            $this->run = true;
        }
    }

### `Migration\Int_::autoIncrement()`

This one is easy - auto-increment never changes:

    protected function autoIncrement(): void {
        if ($this->property->old &&
            $this->property->old->auto_increment !==
            $this->property->new->auto_increment)
        {
            throw new InvalidChange(__("'#[AutoIncrement]' attribute of the ':table.:property' can't be changed", [
                'property' => $this->property->new->name,
                'table' => $this->property->new->parent->table_name,
            ]));
        }
        
        if (!$this->property->new->auto_increment) {
            return;
        }

        if ($this->mode == Property::CREATE ||
            $this->mode == Property::PRE_ALTER)
        {
            if ($this->column) {
                $this->column->autoIncrement();
                $this->run = true;
            }
        }
    }

