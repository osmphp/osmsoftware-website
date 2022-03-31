# Incremental Indexing Roadblocks

I wanted to implement incremental indexing in one go. 

Not so quick. 

I hit some major roadblocks, and I'm working on removing them, one by one.

More details: 

{{ toc }}

### meta.abstract

I wanted to implement incremental indexing in one go.

Not so quick.

I hit some major roadblocks, and I'm working on removing them, one by one.

## Indexing Error

Table notify about their changes, and indexer register these changes in notification tables. All things are set up for implementing incremental indexing.

But before, that, I noticed that the current `osm index` implementation throws errors.

Strange thing - the notification tables (which use indexer IDs) are created, but the `indexers` table is empty.

And `Indexer` objects still remember their IDs.

The only explanation is the following workflow:

1. After clearing cache, `Indexer` objects created status records in the `indexers` table, and got their IDs. If you recall, the status record is created in the getter:

        protected function get_id(): int {
            $id = $this->db->table('indexers')
                ->where('name', $this->name)
                ->value('id');
    
            return $id ?? $this->db->table('indexers')->insertGetId([
                'name' => $this->name,
            ]);
        }

2. After running migrations, the `indexers` table got reset, while `Indexer` objects still remember old IDs in cache.

A quick fix to this issue is to clear the schema cache entry before running schema migrations:

    // Osm\Admin\Schema\Commands\Migrate
    public function run(): void
    {
        global $osm_app; /* @var App $osm_app */
        $osm_app->migrations()->fresh();
        $osm_app->migrations()->up();

        // `$osm_app->schema` is stored in cache. It contains information
        // fetched from the database, for example, indexer IDs. Hence, after
        // resetting the database it's necessary to "forget" the currently
        // cached schema and reflect it from code anew
        $osm_app->cache->deleteItem('schema');
        unset($osm_app->schema);

        $osm_app->schema->migrate();
    }

## Incremental Indexing Entry Point

Let's begin with modeling a situation requiring incremental indexing:

    # create tables
    php bin/run.php migrate:schema
    
    # reset `requires_full_reindex` flags on indexers
    php bin/run.php index
    
    # create 4 product objects
    php bin/run.php migrate:samples
    
After these commands, product search indexer (#9) status is `requires_partial_reindex`, and `zi9__products__inserts` notification table contains 4 product IDs. Running indexing throws the `NotImplemented` exception:

    public function index(string $mode): void {
        if ($mode == static::FULL) {
            $this->fullReindex();
        }
        else {
            throw new NotImplemented($this);
        }
    }

This line should call a new method, `partialReindex()`: 

    protected function partialReindex(): void {
        throw new NotImplemented($this);
    }

I'll return to this method a bit later. 

## Joins In Formula Queries

`Query` object automatically joins related tables. For example, if you mention `category.title` in WHERE clause, it will automatically INNER JOIN `categories` table. It's not implemented yet, but that's the idea.

But here is the thing - it's not enough. How about 

1. selecting from a notification table?
2. joining a notification table? 
3. deleting records from a notification table?

Selecting and deleting notification records can be done with a Laravel query - they don't require any schema knowledge.

Joining a notification table requires some changes in `Query`. I'll return to it the next time.