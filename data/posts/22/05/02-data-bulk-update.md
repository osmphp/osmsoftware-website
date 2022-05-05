# Bulk UPDATE

Let's implement the `Query::bulkUpdate()` method in the TDD way.

**Later**. Lots of query features don't have test coverage yet.

Contents:

{{ toc }}

### meta.abstract

The `Query::bulkUpdate()` method is implemented in the TDD way.

## Failing Test

Before implementing it, I need a test using it that fails:

    public function test_bulk_insert(): void {
        // GIVEN a schema defined in the `Osm\Admin\Samples\Queries\V001`
        // namespace, and some data
        $id = query(Product::class)->insert([
            'title' => 'Lorem ipsum',
        ]);

        // WHEN you bulk update descriptions
        query(Product::class)
            ->select("description ?? '-' AS description")
            ->bulkUpdate();
        $description = query(Product::class)
            ->where("id = {$id}")
            ->value("description");
            
        // THEN they indeed change
        $this->assertEquals('-', $description);
    }

## `Formula\Operator::castToFirstNonNull()`

An unimplemented `castToFirstNonNull()` popped out. While moving formula query code from an older project, I kept some parts not implemented, and it's going to haunt me for a while.

This method should return a type name of a coalescing operator. For example, `id ?? NULL` returns `int`, as `id` property is integer.

Here is the implementation:

    // Osm\Admin\Queries\Formula\Operator
    protected function castOperandsToFirstNonNull(): string {
        $dataType = 'mixed';
        foreach ($this->operands as $operand) {
            if ($operand->data_type->type !== 'mixed') {
                $dataType = $operand->data_type->type;
                break;
            }
        }

        $this->castOperandsTo($dataType);

        return $dataType;
    }

    protected function castOperandsTo(string $dataType): void {
        foreach ($this->operands as &$operand) {
            $operand = $operand->castTo($dataType);
        }
    }

    // Osm\Admin\Queries\Formula
    protected function castTo(string $dataType): Formula {
        if ($this->data_type->type === $dataType) {
            return $this;
        }

        $cast = Formula\Cast::new([
            'formula' => $this,
            'parent' => $this->parent,
            'data_type' => $this->data_types[$dataType],
            'array' => $this->array,
        ]);

        $this->parent = $cast;

        return $cast;
    }
 
## Generating Bulk UPDATE SQL

Again, back to the `bulkUpdate()` method. Here is how I implemented it:

    // Osm\Admin\Queries\Query
    public function bulkUpdate(): void {
        // generate and execute SQL UPDATE statement
        $bindings = [];
        $sql = $this->generateBulkUpdate($bindings);
        $this->db->connection->update($sql, $bindings);
    }

    protected function generateBulkUpdate(array &$bindings): string {
        $from = [$this->table->table_name => true];
        $updates = $this->generateBulkUpdates($bindings, $from);
        $where = $this->generateWhere($bindings, $from);

        return <<<EOT
    UPDATE {$this->generateFrom($from)}
    SET {$updates}
    {$where}
    EOT;
    }

    protected function generateBulkUpdates(array &$bindings, array &$from)
        : string
    {
        $sql = '';

        if (empty($this->selects)) {
            throw new InvalidQuery(__("Add a select expression to the query."));
        }

        foreach ($this->selects as $formula) {
            if ($sql) {
                $sql .= ', ';
            }

            $sql .= "`{$formula->alias}` = " .
                $formula->expr->toSql($bindings, $from, 'LEFT OUTER');
        }

        return $sql;
    }

Unlike the `update()` method, it doesn't do any validation.

## Putting Indexing On Hold

Still, it should notify listeners about changes, and initiate partial reindexing.

But here is a catch. If indexing is synchronous, it updates dependent tables recursively right away. But these tables may not be migrated yet, and due actual data being incompatible with what an indexer expect it to be (for example, missing new columns), it may fail.

The solution is to allow the caller to take responsibility for initiating partial reindexing, like this:

    $requiresReindex = $schema->dontIndex(function() {
        ...
    });
    
    // caller may initiate re-indexing if called code wanted to initiate it
    if ($requiresReindex) {
        $schema->index();
    }

Here is how it works internally:

    // Osm\Admin\Schema\Schema
    public function index(string $mode = Indexer::PARTIAL): void {
        if ($this->dont_index_depth) {
            $this->dont_index_requested = true;
            return;
        }
        ...
    }

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

## Notifying Listeners

Here is an updated version of the `bulkUpdate()` method that takes indexing into account:

    public function bulkUpdate(): void {
        $this->db->transaction(function() {
            // generate and execute SQL UPDATE statement
            $bindings = [];
            $sql = $this->generateBulkUpdate($bindings);
            $this->db->connection->update($sql, $bindings);

            $this->db->committing(function() {
                // validate modified objects as a whole, and their
                // dependent objects
                $this->validateObjects(static::UPDATED);

                // create notification records for the dependent objects in
                // other tables, and for search index entries
                $this->notifyListeners(static::UPDATED);
            });

            // register a callback that is executed after a successful transaction
            $this->db->committed(function()
            {
                // successful transaction guarantees that current objects are
                // fully up-to-date (except aggregations), so it's a good time to
                //make sure that asynchronous indexing is queued, or to execute
                // it right away if queue is not configured. All types of asynchronous
                // indexing are queued/executed: regular, aggregation and search.
                $this->updateDependentObjects();
            });
        });
    }

## Finalizing Migration Test

Remember that [unfinished migration test](../04/28-data-adding-explicit-properties-data-conversions.md#test)? It's time to add assertions to it, and to check if everything works:

    public function test_make_explicit_property_non_nullable() {
        // GIVEN database with `V2` schema and some data
        $id = query(Product::class)
            ->where("title = 'Lorem ipsum'")
            ->value("id");

        // WHEN you run `V3` migration
        $this->loadSchema(Product::class, 3);
        $this->app->schema->migrate();

        // THEN NULL values are converted to falsy values
        $this->assertEquals('-', $this->app->db->table('products')
            ->where('id', $id)
            ->value('description'));
    }

And indeed, the test works!
