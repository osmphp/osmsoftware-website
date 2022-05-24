# Finishing Diff-Based Migrations

Yay! After implementing invalid data conversion, I finished the iteration #18 dedicated to diff-based migrations!

Here is how it went:

{{ toc }}

### meta.abstract

Yay! After implementing invalid data conversion, I finished the iteration #18 dedicated to diff-based migrations!

## Converting Invalid Values 

During a type conversion, invalid values should be replaced with default values.

For example:

    // Osm\Admin\TestsMigrations\test_03_strings::test_conversion_to_int()
    $id2 = ui_query(Product::class)->insert((object)[
        'title' => 'Invalid color',
        'description' => 'Invalid color',
        'color' => 'black', // non-numeric
    ]);

Currently, it doesn't happen, and MySql throws an error:

    Incorrect integer value: 'black'
    
### Casting Values

Where should I add it? `type` handling triggers data conversion to happen, but it doesn't change the current value in any way:

    // Osm\Admin\Schema\Diff\Property
    protected function type(): void {
        $this->attribute('type', function() {
            ...
            $this->convert();
        });
    }

Let's add the type conversion logic:

    protected function type(): void {
        $this->attribute('type', function() {
            ...
            $this->convert(fn(string $value) => "SAFE_CAST({$value}, " .
                "'{$this->new->data_type->type}', {$this->new->default_value})");
        });
    }

### Logging Migration Queries

For some reason, the column type is changed in the pre-alter phase, though it should have waited after data conversion ends. 

To tackle this issue, it's useful to see all queries issued during the migration.

Here is how it's done:

    // Osm\Admin\Schema\Diff\Schema
    protected bool $migrating = false;

    public function migrate(): void {
        $this->migrating = true;

        $this->db->connection->listen(function (Events\QueryExecuted $query) {
            if ($this->migrating) {
                $this->log->info($query->sql, [
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                ]);
            }
        });

        ...
        
        $this->migrating = false;
    }

From the log, I see that DDL statements were executed after the data conversion in previous migrations, but not in this phase. 

### Running Migration Step Only If It's Needed

I also added additional check that a property migration step should be executed - at all, and it's only executed if so:

    public function migrate(string $mode, Blueprint $table = null,
        Query $query = null): bool
    {
        if (!$table && !$query) {
            if (!isset($this->run[$mode])) {
                $this->run[$mode] = $this->doMigrate($mode);
            }

            return $this->run[$mode];
        }

        return $this->migrate($mode) && $this->doMigrate($mode, $table, $query);
    }

    protected function doMigrate(string $mode, Blueprint $table = null,
        Query $query = null): bool
    {
        ... old logic ...
    }

### `RENAME` Migration Step

Yet, why type don't wait for the data conversion to happen?

It turns out, the Laravel schema builder first creates/changes columns and then renames columns, always in this order.

It means that for renaming column, a separate step or ($mode) is needed.

Let's implement it:

    /**
     * ...
     * @property bool $requires_rename `true` if any property diff requires renaming
     *      existing column
     */
    class Table extends Diff
    {
        protected function rename(): void {
            if ($this->requires_rename) {
                $this->log(__("Renaming ':table' table columns", [
                    'table' => $this->new->table_name,
                ]));
    
                $this->db->alter($this->new->table_name, function(Blueprint $table) {
                    foreach ($this->properties as $property) {
                        $property->migrate(Property::RENAME, table: $table);
                    }
                });
            }
        }
    
        protected function alter(): void {
            $this->rename();
            ...
        }
    
        protected function get_requires_rename(): bool {
            foreach ($this->properties as $property) {
                if ($property->migrate(Property::RENAME)) {
                    return true;
                }
            }
    
            return false;
        }
        ...
    } 
    
    class Property extends Diff
    {
        ...
        
        public const RENAME = 'rename';
    
        ...
        
        protected function doMigrate(string $mode, Blueprint $table = null,
            Query $query = null): bool
        {
            /** @noinspection PhpBooleanCanBeSimplifiedInspection */
            return match ($mode) {
                ...
                static::RENAME =>
                    $this->renameExistingData($table),
                ...
            };
        }
    
        protected function renameExistingData(?Blueprint $table): bool {
            $run = false;
    
            if ($this->rename_old_column) {
                $table?->renameColumn($this->old->name, "old__{$this->old->name}");
                $run = true;
            }
    
            return $run;
        }
    
        protected function beforeMigratingData(?Blueprint $table): bool {
            $run = false;
    
            if ($this->rename_old_column) {
                $this->migrateColumn($table);
                $run = true;
            }
    
            return $run;
        }
        ...
    }
    
### `SAFE_CAST` Function

Implementation:

    // Osm\Admin\Queries\Function_\SafeCast
    #[Type('safe_cast')]
    class SafeCast extends Function_
    {
        public function resolve(Formula\Call $call, Table $table): void {
            $this->argCountIs($call, 3);
            $this->argIsTypedLiteral($call, 1, 'string');
    
            $dataType = $call->args[1]->value();
    
            if (!isset($this->data_types[$dataType])) {
                throw new InvalidCall(
                    __("Pass a valid data type name to the 2-nd argument of the ':function' function", [
                        'function' => strtoupper($call->type),
                    ]),
                    $call->formula, $call->pos, $call->length);
            }
    
            $call->data_type = $this->data_types[$dataType];
            $call->array = false;
        }
    
        // Osm\Admin\Schema\DataType
        public function toSql(Formula\Call $call, array &$bindings,
            array &$from, string $join): string
        {
            return $call->data_type->safeCastToSql($call->args[0], $call->args[2],
                $bindings, $from, $join);
        }
    
    }
    
    public function safeCastToSql(Formula $formula, Formula $default,
        array &$bindings, array &$from, string $join): string
    {
        return $this->castToSql($formula, $bindings, $from, $join);
    }

    // Osm\Admin\Schema\DataType\Int_
    public function safeCastToSql(Formula $formula, Formula $default,
        array &$bindings, array &$from, string $join): string
    {
        if ($formula->data_type->type === 'string') {
            return "IF(" .
                "({$formula->toSql($bindings, $from, $join)}) " .
                    "REGEXP '^[[:space:]]*[[:digit:]]+[[:space:]]*$', ".
                "0 + REGEXP_REPLACE({$formula->toSql($bindings, $from, $join)}, " .
                    "'(^[[:space:]]+|[[:space:]]+$)', ''), " .
                $default->toSql($bindings, $from, $join) .
            ")";
        }

        return parent::safeCastToSql($formula, $default, $bindings,
            $from, $join);
    }

And after running tests, it finally works!

## Farewell To Iteration No. 18

After fighting (and winning over) the 1000-th problem, suddenly, everything starts to work.

A very strange feeling...

Diff-based migration are far from finished, and yet, it's good enough to sketch in the rest functionality, and to get the feeling of what the developer experience is going to be like.  