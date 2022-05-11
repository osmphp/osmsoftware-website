# Migration Classes

I refactored `Property::migrate()` using additional `Migration` classes. It's so much more convenient to compare property definition versions and generate migration SQLs!

New code structure already handles property explicitness changes.

Here are all the details:  

{{ toc }}

### meta.abstract

I refactored `Property::migrate()` using additional `Migration` classes. It's so much more convenient to compare property definition versions and generate migration SQLs!

New code structure already handles property explicitness changes.

## Extracting Migration Logic Into Additional Class

Before diving into `Property::migrate()`, I'd like to fix a code smell first. It receives its initial state in parameters, and then passes the state to other methods:

    public function migrate(string $mode, Blueprint $table = null): bool {
        ...
        
        // if it's a new property, migration should run no matter what
        $run = $mode === static::CREATE;

        $column = $this->column($table);
        $run = $this->type($mode, $table) || $run;
        $run = $this->unsigned($mode, $column) || $run;
        $run = $this->nullable($mode, $column) || $run;
        $this->change($mode, $column);
        ...

        return $run;
    }

Much more maintainable is to create an object holding the state and call its `migrate()` method:

    public function migrate(string $mode, Blueprint $table): void {
        if (!$this->requiresMigration($mode)) {
            return;
        }

        $new = "{$this->migration_class_name}::new";
        
        $new([
            'property' => $this,
            'mode' => $mode, 
            'table' => $table,
        ])->migrate();
    }

In addition, when Osm Admin merely checks if a property migration is required, migration object references can be remembered and reused:
    
    public function requiresMigration(string $mode): bool {
        if (!isset($this->migrations[$mode])) {
            $new = "{$this->migration_class_name}::new";
            $this->migrations[$mode] = $new([
                'property' => $this,
                'mode' => $mode, 
            ]);
        }
        
        return $this->migrations[$mode]->migrate();
    }

## Base `Migration` Class

Property migration classes are located in the `Osm\Admin\Schema\Diff\Migration` namespace. 

The base class currently doesn't do much:

    /**
     * @property Property $property
     * @property string $mode
     * @property ?Blueprint $table
     */
    class Migration extends Object_
    {
        protected bool $run = false;
    
        protected function get_property(): Property {
            throw new Required(__METHOD__);
        }
    
        protected function get_mode(): string {
            throw new Required(__METHOD__);
        }
    
        public function migrate(): bool {
            throw new NotImplemented($this);
        }
    }
    
## `Schema::dontIndex()` "Eats" Exceptions

I noticed that for some reason, in case of exception the following code in the `Schema::dontIndex()` doesn't stop execution:

    $requiresReindex = $this->dontIndex(function() use($schema) {
        $schema->migrate();
    });

Why?

Here is its source code:

    public function dontIndex(callable $callback): bool {
        if (!$this->dont_index_depth) {
            $this->dont_index_requested = true;
        }

        $this->dont_index_depth++;

        try {
            $callback();
        }
        finally {
            $this->dont_index_depth--;

            return $this->dont_index_depth
                ? false
                : $this->dont_index_requested;
        }
    }

It turns out, the `return` statement in the `finally` block silences all the exceptions in the `try` block!

Let's fix it:

    public function dontIndex(callable $callback): bool {
        if (!$this->dont_index_depth) {
            $this->dont_index_requested = true;
        }

        $this->dont_index_depth++;

        try {
            $callback();
        }
        finally {
            $this->dont_index_depth--;
        }

        return $this->dont_index_depth
            ? false
            : $this->dont_index_requested;
    }

## `Migration\Int_` Class

Back to migration classes, let's start with `int` property migrations:

    public function migrate(): void {
        $this->init();
        $this->explicit();
        $this->type();
        $this->nullable();
        $this->size();
        $this->unsigned();
        $this->autoIncrement();
    }

## `Migration::init()`

Before even diffing the property definition, let's create a column DDL definition and a property formula that the diffing algorithm will modify as needed:

    protected function init(): void {
        switch ($this->mode) {
            case Property::CREATE:
            case Property::PRE_ALTER:
            case Property::POST_ALTER:
                // in a DDL migration, prepare a column definition
                // that *may* be used in actual DDL statement if other
                // methods of this class report that such a migration is needed.
                if ($this->table && $this->property->new->explicit) {
                    $this->column = $this->column();

                    if ($this->property->old?->explicit) {
                        // if the property column already exists in
                        // the database change the existing column
                        // instead of creating the
                        // new one 
                        $this->column->change();
                    }
                }
                break;
            case Property::CONVERT:
                // in value migration SQL, prepare initial conversion formula 
                if ($this->query && $this->property->old) {
                    $this->old_value = $this->property->old->explicit
                        ? "COLUMN('{$this->property->old->name}')"
                        : "DATA('{$this->property->old->name}')";
                        
                    // the `{{old_value}}` placeholder will be replaced
                    // before running the actual query    
                    $this->new_value = "{{old_value}}";     
                }
                break;
        }
    }

## `Migration::explicit()`

Here are possible explicitness changes:

* If a property becomes explicit, a column is created. 
* If the property becomes implicit, its column is dropped. 
* If the property stays implicit, no migration is needed. 
* If a property stays explicit, its column may be altered, depending on other changes.

The implementation is below:

    protected function explicit(): void {
        if ($this->property->new->explicit) {
            if (!$this->property->old?->explicit) {
                $this->becomeExplicit();
            }
        }
        else {
            if ($this->property->old?->explicit) {
                $this->becomeImplicit();
            }
        }
    }

    /**
     * If a property becomes explicit, a column is created. If the property
     * already exists, its data is converted using default conversion formula 
     */
    protected function becomeExplicit(): void {
        switch ($this->mode) {
            case Property::CREATE:
            case Property::PRE_ALTER:
                $this->run = true;
                break;
            case Property::CONVERT:
                if ($this->property->old) {
                    $this->run = true;
                }
                break;
            case Property::POST_ALTER:
                break;
        }
    }

    /**
     * If the property stops being explicit, its column is dropped. It may 
     * only happen on ALTER TABLE, after the data conversion.
     */
    protected function becomeImplicit(): void {
        switch ($this->mode) {
            case Property::CREATE:
            case Property::PRE_ALTER:
                break;
            case Property::CONVERT:
                if ($this->property->old) {
                    $this->run = true;
                }
                break;    
            case Property::POST_ALTER:
                $this->run = true;
                if ($this->table) {
                    $this->table->dropColumn($this->property->old->name);
                }
                break;
        }
    }

