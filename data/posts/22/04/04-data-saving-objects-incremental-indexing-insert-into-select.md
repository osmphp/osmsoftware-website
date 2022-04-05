# Saving Objects. Incremental Indexing. `INSERT INTO ... SELECT ...`

New month, same worries - I continued working on Osm Admin:

* routes responsible for creating new products, and modifying existing products
* incremental indexing
* cloning query objects
* `INSERT INTO ... SELECT ...` statements 

More details:

{{ toc }}

### meta.abstract

Done:

* routes responsible for creating new products, and modifying existing products
* incremental indexing
* cloning query objects
* `INSERT INTO ... SELECT ...` statements

## `Query::$from`

When generating SQL, `Query::generate...()` methods fill in the temporary `from` property, and then it's converted into the `FROM` clause:

    // Query::generateSelect()
    FROM {$this->generateFrom($from)}
    
    ...
    protected function generateFrom(array $from): string
    {
        $sql = '';

        ksort($from);

        foreach ($from as $alias => $on) {
            if ($on !== true) { // if it's a JOIN
                $sql .= "\n    {$on}";
                continue;
            }

            // otherwise, it's the main table, or a singleton
            if ($sql) {
                $sql .= ", \nFROM ";
            }

            $sql .= "`{$alias}`";
        }

        return $sql;
    }
    
As you can see, keys in `from` array are table aliases, and value is either `true` or a string containing `JOIN` clause.

For example, given the following sample `from` array:

    $from = [
        'products' => true,
        'products__category' => "INNER JOIN `categories` AS `products__category` " . 
            "\n    ON `products__category`.`id` = `products`.`category_id`"
        'settings' => true,
    ];  

`Query` generates the following SQL statement:

    SELECT ...
    FROM `products`
    INNER JOIN `categories` AS `products__category`
        ON `products__category`.`id` = `products`.`category_id`,
    FROM `settings`
    ...    
    
## Generating Notification Table Join

Having this knowledge, the incremental search indexing or new objects works as follows:

    // Search\Index
    protected function partialReindex(): void {
        $listensTo = $this->listens_to[$this->table->name];

        // copy new entries
        $query = $this->query()->joinInsertNotifications($this);
        foreach ($query->get() as $item) {
            $this->searchQuery()->insert((array)$item);
        }

        // delete processed insert notifications
        $notificationTable = $this->getNotificationTableName($this->table,
            $listensTo[Query::INSERTED]);
        $this->db->table($notificationTable)->delete();
        
        ...
    }

    // Query
    public function joinInsertNotifications(Indexer $indexer,
        string $identifier = 'id'): static
    {
        return $this->joinNotifications($indexer, static::INSERTED,
            $identifier);
    }

    public function joinNotifications(Indexer $indexer, string $event,
        string $identifier): static
    {
        $this->notification_joins[] = [
            'identifier' => $this->parse($identifier, Formula::IDENTIFIER),
            'notification_table' => $indexer->getNotificationTableName(
                $this->table, $indexer->listens_to[$this->table->name][$event]),
        ];

        return $this;
    }

    protected function generateSelect(array &$bindings): string
    {
        ...
        $this->generateNotificationJoins($bindings, $from);
        ...
    }
    
    protected function generateNotificationJoins(array &$bindings,
        array &$from): void
    {
        foreach ($this->notification_joins as $join) {
            $table = $join['notification_table'];
            /* @var Formula\Identifier $identifier */
            $identifier = $join['identifier'];

            $from[$table] = <<<EOT
    INNER JOIN `$table`
            ON `$table`.`id` = {$identifier->toSql($bindings, $from, 'INNER')}
    EOT;
        }
    }

And it works!

Here the generated SQL that selects new products:

    SELECT `products`.`id` AS `id`, 
        `products`.`_data`->>'$."title"' AS `title`, 
        `products`.`_data`->>'$."color"' AS `color`
    FROM `products`
        INNER JOIN `zi9__products__inserts`
            ON `zi9__products__inserts`.`id` = `products`.`id`

## Triggering Incremental Indexing After Insert

`osm index` command works. However, running it after creating each product is not how it supposed to be. Indexing should run automatically.

I already defined the [indexing behavior earlier](../03/09-data-returning-to-building-in-public-facets-search-indexing.md#asynchronous-indexing):

> Then, a separate process runs that processes records in the notification tables, updates target objects, and clears processed notification records.

The idea was that the separate process is a queue worker, and `Query` operation should put a queued job for it. I'll implement queues later.

For now, I'll run the indexing in the same process:

    // Query
    public function insert(array $data): int {
        ...
        return $this->db->transaction(function() use($data) {
            ...
            // register a callback that is executed after a successful transaction
            $this->db->committed(function()
            {
                // successful transaction guarantees that current objects are
                // fully up-to-date (except aggregations), so it's a good time to
                // make sure that asynchronous indexing is queued, or to execute
                // it right away if queue is not configured. All types of asynchronous
                // indexing are queued/executed: regular, aggregation and search.
                $this->updateDependentObjects();
            });

            return $id;
        });
    }

    protected function updateDependentObjects(): void {
        $this->table->schema->index();
    }

Lucky day, indeed. It works, too!

## `POST /`

The route that handles the editing form, `POST /` is similar to `POST /create` - the one that creates a new object:

    #[Ui(Admin::class), Name('POST /')]
    class Edit extends Route
    {
        public function run(): Response
        {
            $item = json_decode($this->http->content, flags: JSON_THROW_ON_ERROR);
            if (!is_object($item)) {
                return plain_response(__("Object expected"), 500);
            }
    
            $query = ui_query($this->table->name);
    
            $query
                ->fromUrl($this->http->query,
                    'limit', 'offset', 'order', 'select')
                ->update($item);
    
            return json_response((object)[]);
        }
    }

## `Query::clone()`

The `POST /` route throws an error:

    Call to undefined method Osm_Admin_Samples\Osm\Admin\Queries\Query::clone()
    
    #0 /home/vo/projects/admin2/src/Queries/Query.php(587)

[Earlier](../03/28-data-post-create-db-only-queries-on-editing-page-change-notifications.md#handling-change-notifications), I left a technical debt:

> **Note**. `$query->clone()` and `$query->into()` will be implemented later.

Now it's time to return it.

The line using the undefined `clone()` and `into()` methods, looks as follows:

    $query->clone()->select('id')->into($tableName);
    
The idea of `clone()` is to take the WHERE part of the UPDATE query, which is:

    UPDATE `products`
    SET `_data` = JSON_SET(`_data`, '$."color"', ?)
    WHERE (`products`.`id` IN (?, ?, ?)) 

Later, I may want to clone other parts of a query, too, so let's add the `filters` flag to the method signature:

    $query
        ->clone(where: true)
        ->select('id')
        ->into($tableName);

Here is the `clone()` method itself:

    // Osm\Admin\Queries\Query
    public function clone(bool $where = false): static {
        $query = static::new(['table' => $this->table]);

        if ($where) {
            foreach ($this->filters as $formula) {
                $query->filters[] = $formula->clone();
            }
        }
        
        return $query;
    }

    // Osm\Admin\Queries\Formula
    public function clone(): Formula {
        $formula = static::new();
        
        foreach ($this as $propertyName => $value) {
            if (!($property = $this->__class->properties[$propertyName] ?? null)) {
                continue;
            }
            
            if (!isset($property->attributes[Serialized::class])) {
                continue;
            }
            
            if (!is_a($property->type, Formula::class, true)) {
                $formula->$propertyName = $value;
                continue;
            }
            
            if (!$property->array) {
                /* @var Formula $value */
                $formula->$propertyName = $value->clone();
                $value->parent = $formula;
                continue;
            }

            /* @var Formula[] $value */
            $formula->$propertyName = [];
            foreach ($value as $key => $item) {
                $formula->$propertyName[$key] = $item->clone();
                $item->parent = $formula;
            }
        }
        
        return $formula;
    }

## `Query::into()`

This method is for generating 

    INSERT IGNORE INTO ...
    SELECT ...
    
First, let's reserve `into()` method name for mass inserting objects into object tables, and notifying indexers along the way.

Now, a method is needed for notification tables only, so let's name it accordingly:

    // Osm\Admin\Queries\Query
    public function intoNotificationTable(): void {
        throw new NotImplemented($this);
    }

Inside, it should generate the SELECT as usual: 

    public function intoNotificationTable(string $tableName): void {
        $bindings = [];
        $sql = "INSERT IGNORE INTO `{$tableName}` (`id`)\n";
        $sql .= $this->generateSelect($bindings);

        $this->db->connection->insert($sql, $bindings));
    }
 