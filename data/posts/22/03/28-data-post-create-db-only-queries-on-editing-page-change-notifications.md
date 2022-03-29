# `POST /create`. DB-Only Queries On Editing Page. Change Notifications

Yesterday results: 

* `POST /create` route handles the input of new object form.
* A decision to keep faceted navigation and search out of the editing page.
* Sending table change notifications.

More about it: 

{{ toc }}

### meta.abstract

Yesterday results:

* `POST /create` route handles the input of new object form.
* A decision to keep faceted navigation and search out of the editing page.
* Sending table change notifications.

## Error In URL Parameter Parsing

The next thing I've got is an error on the editing page.

    // GET /products/edit?color=blue+pink&id-=1
    '' record doesn't have 'blue' property
    #0 src/Queries/Formula/In_.php(43)
    
What's going on? 

The problem is that the string values `blue` and `pink` weren't enclosed into quotes, so they were treated as property names rather than string literals.  

So instead of using values as is:

    $query->where("{$this->property_name} IN (" .
        implode(', ', $this->items) .
        ")");  
        
I added `?` parameter placeholders, and passed values as query bindings:

    $questionMarks = implode(', ',
        array_fill(0, count($this->items), '?'));

    $query->where("{$this->property_name} IN ($questionMarks)",
        ...$this->items);

## `POST /create`

At the moment, both grid and form are rendered, and you can navigate back and forth. 

Let's implement actual editing actions starting from `POST /create`.

It's been quite good in `v0.1`, so I copied and adapted it:

    #[Ui(Admin::class), Name('POST /create')]
    class Create extends Route
    {
        public function run(): Response
        {
            $item = json_decode($this->http->content, flags: JSON_THROW_ON_ERROR);
            if (!is_object($item)) {
                return plain_response(__("Object expected"), 500);
            }
    
            $query = ui_query($this->table->name);
    
            $id = $query->insert($item);
    
            return json_response((object)[
                'url' => $query->toUrl('GET /edit', [
                    UrlAction::setParameter('id', (string)$id)
                ]),
            ]);
        }
    }

## Editing Page Should Use DB-Only Queries

The object creation story is not over - the new product is not shown in the product grid, as its search index is not updated. It's good time to implement incremental search index updates.

However, before doing it, one thought bothers me. 

Search engines work best when used asynchronously. That is, if you have issued an operation to add a product to the search index, don't expect it to be there right away.  

On the other hand, after creating a product, you are redirected to the product editing page, and this page may use the search index to query this record, either for rendering faceted navigation counts, or for applying a full-text search filter.

The same issue is relevant to product editing, too. After saving your edits, the product may not match the filter criteria set in the URL query parameters, and won't be found.

Finally, the same issue is relevant to the route that actually apply edits, or delete products.

The best solution would be to check whether the products are fully indexed, and until then, use DB-only queries. However, I'm not sure if it's possible. Even if the DB indicates that the search index is up-to-date, it may still be processed due to its async nature.

Another solution is to index the search index synchronously. However, page performance would degrade.   

Right now, I can't think of anything better than avoiding search-related features at all.

One such feature is faceted navigation. Indeed, this feature is nice-to-have. However, I'll still need to find a way to display currently applied filters. More on that later, when I implement rendering of applied filters.

Another feature is full-text search. For example, after searching for `lorem ipsum`, Osm Admin will show `/products/?q=lorem+ipsum` page. If, then, I select all products, the `Edit` button should collect all matching product IDs: `/products/edit?id=1+2+3`. If there are too many products matching the selection, the `Edit` button should not be available. Same with the `Delete` button. More on that later, when I implement full-text search.

And the last thing to mention. All properties shown in faceted navigation and in grid columns should be stored in explicit database columns, and indexed. Again, I'll return to this when I implement  

## Incremental Search Indexing

Previous effort:

* [Indexing](09-data-returning-to-building-in-public-facets-search-indexing.md#indexing)
* [Full Search Reindexing](10-data-full-search-reindexing.md)

Here is what I'm going to implement:

> Regular indexing, search indexing and aggregation indexing are executed asynchronously. It means that during INSERT/UPDATE/DELETE operation, Osm Admin remembers ID of the affected source object in the notification tables, but doesn't modify any target object.

The INSERT logic is actually well-prepared for adding indexing to it:

    // Osm\Admin\Queries\Query
    public function insert(array $data): int {
        // all input data is validated before running a transaction
        $this->validateProperties(static::INSERTING, $data);

        return $this->db->transaction(function() use($data) {
            // generate and execute SQL INSERT statement
            $bindings = [];
            $sql = $this->generateInsert($data, $bindings);

            $this->db->connection->insert($sql, $bindings);
            $data['id'] = $id = 
                (int)$this->db->connection->getPdo()->lastInsertId();

            // compute regular, self and ID-based indexing expressions by
            // running an additional UPDATE. Note that property-level validation
            // rules on computed values are not executed - take care in formulas
            $this->computeProperties(static::INSERTED, $data);

            // register a callback that is executed after a successful transaction
            $this->db->committing(function() use ($data)
            {
                // validate modified objects as a whole, and their
                // dependent objects
                $this->validateObjects(static::INSERTED);

                // create notification records for the dependent objects in
                // other tables, and for search index entries
                $this->notifyDependentObjects(static::INSERTED, $data);
            });

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

Let's start by uncommenting the `NotImplemented` exception:

    protected function notifyDependentObjects(string $event, array $data): void
    {
        throw new NotImplemented($this);
    }

Basically, this method notifies whoever listens to changes made in this table. Let's rename the method to reflect that:

    protected function notifyListeners(string $event, array $data = []): void {
        foreach ($this->table->listeners as $listener) {
            $listener->notify($this, $event, $data);
        }
    }

Currently, `listeners` are all indexers. Later, if I decide to push live updates to browsers, they may be considered as listeners as well.

To avoid infinite recursion during schema serialization, `Table::$listeners` takes the listeners from schema instead of computing and caching it right in the `Table` class:

    // Osm\Admin\Schema\Table
    protected function get_listeners(): array {
        return array_map(fn(string $name) => $this->schema->indexers[$name],
            $this->schema->listener_names[$this->name]);
    }
    
    // Osm\Admin\Schema\Schema
    protected function get_listener_names(): array {
        $listenerNames = array_map(fn(Table $table) => [], $this->tables);

        foreach ($this->indexers as $indexer) {
            foreach (array_keys($indexer->listens_to) as $tableName) {
                $listenerNames[$tableName][] = $indexer->name;
            }
        }

        return $listenerNames;
    }

The indexer subscribes to specified events by providing a suffix for a notification table that will store pending notifications. For example, products insert notifications for its search index are stored in the `products__{indexer_id}__inserts` table:

    // Osm\Admin\Schema\Indexer\Search
    protected function get_listens_to(): array {
        return [
            $this->table->name => (object)[
                Query::INSERTED => 'inserts',
                Query::UPDATED => 'updates',
                Query::DELETED => 'deletes',
            ],
        ];
    }

## Handling Change Notifications
    
Indexers just save pending IDs into the notification table:

    public function notify(Query $query, string $event, array $data): void {
        $listensTo = $this->listens_to[$query->table->name];

        if (!($tableSuffix = $listensTo[$event] ?? null)) {
            // do nothing if the indexer is not listening to $event
            return;
        }
        
        $tableName = "{$this->table->name}__{$this->id}__{$tableSuffix}";

        if ($event == Query::INSERTED) {
            $this->db->table($tableName)->insert([
                'id' => $data['id'],
            ]);
        }
        else {
            $query->clone()->select('id')->into($tableName);
        }
        
        $this->markAsRequiringPartialReindex();
    }

**Note**. `$query->clone()` and `$query->into()` will be implemented later.

