# Creating Tables. Creating Notification Tables

Yesterday, I moved the table creation code into new schema diff migration engine.

Now, working on notification table migrations.

Details:

{{ toc }}

### meta.abstract

Yesterday, I moved the table creation code into new schema diff migration engine.

Now, working on notification table migrations.

## Where Am I?

After refactoring schema diff algorithm, which is still work in progress, I decided to TDD migration code case by case.

The first case is creation of table storing two standard properties: `id` and `title`.

`Migrator` renamed to `Diff`, so it's `Diff\Schema`, `Diff\Table` and so on.

## Schema Migration

Given a diff of two schemas, the migration is quite simple:

    // Osm\Admin\Schema\Diff\Schema
    public function migrate(): void {
        foreach ($this->tables as $table) {
            $table->migrate();
        }
    }

    // Osm\Admin\Schema\Diff\Table
    public function migrate(): void {
        if ($this->alter) {
            $this->alter();
        }
        else {
            $this->create();
        }
    }

This logic creates new tables or alters existing tables.

**Later**. There will be more steps: dropping old tables, creating/dropping notification tables, enqueuing/running indexers, and more.

**Notice**. I document all the technical debt in "Later" sections. And it's not really "debt". I think now that I *should* implement it later, but when the "later" comes, it may not be relevant anymore, or there may be more important things to do. These are more like messages in the bottle that I send for the future me.

## Table Creation/Altering

I moved the table creation from the existing code:

    // Osm\Admin\Schema\Diff\Table
    protected function create(): void {
        $this->db->create($this->new->table_name, function(Blueprint $table) {
            foreach ($this->properties as $property) {
                $property->migrate($table);
            }

            $table->json('_data')->nullable();
            $table->json('_overrides')->nullable();
        });
    }

    protected function alter(): void {
        if ($this->requires_alter) {
            $this->db->alter($this->new->table_name, function(Blueprint $table) {
                foreach ($this->properties as $property) {
                    $property->migrate($table);
                }
            });
        }
    }

**Later**. `alter()` will handle table renames, dropping old columns, and more. 

## Column Creation/Altering

First, there is nothing to do if a property is not explicit:

    if (!$this->new->explicit) {
        return;
    }

Second, currently, table columns are created in multiple methods: `Property\Bool_::create()`, `Property\String_::create()`, and so on. Some logic there is type-specific, some is generic. 

Should I create a `Diff\Property\*` class for each property type, or keep everything on one class? Frankly, I don't know. Most probably, yes, for the same reasons there is a different `Property\*` classes. 

Or, maybe I should call type-specific `create()` and `alter()` methods of the property object:

    // Osm\Admin\Schema\Diff\Property
    public function migrate(Blueprint $table): void {
        if (!$this->new->explicit) {
            return;
        }

        if ($this->alter) {
            $this->alter($table);
        }
        else {
            $this->create($table);
        }
    }

    protected function create(Blueprint $table): void {
        $this->new->create($table);
    }

    protected function alter(Blueprint $table): void {
        $this->new->alter($table, $this);
    }

## Notification Tables

The first test passed!

Now, the second test fails on INSERT:

    $id = ui_query(Product::class)->insert((object)[
        'title' => 'Lorem ipsum',
    ]);

The error message is:

    Base table or view not found: 1146 Table 'admin2.zi1__products__inserts' doesn't exist
    
Previously, notification tables were created just after the "source" table creation:

    // Osm\Admin\Schema\Table
    foreach ($this->listeners as $listener) {
        $listener->createNotificationTables($this);
    }

    // Osm\Admin\Schema\Indexer
    public function createNotificationTables(Table $source): void  {
        $listensTo = $this->listens_to[$source->name];

        if ($inserted = $listensTo[Query::INSERTED] ?? null) {
            $this->createNotificationTable($source, $inserted, cascade: true);
        }

        if (($updated = $listensTo[Query::UPDATED] ?? null) &&
            $updated != $inserted)
        {
            $this->createNotificationTable($source, $updated, cascade: true);
        }

        if ($deleted = $listensTo[Query::DELETED] ?? null) {
            $this->createNotificationTable($source, $deleted, cascade: false);
        }
    }

Such structure doesn't fit other use cases:

* If a listener no longer listens to the source table, notification tables should be dropped.
* If new listener wants to listen to the source table, notification tables should be created.
* If a source table is dropped, notification tables should be dropped as well.
* If a source table is renamed, notification tables should be renamed.
* If an indexer is deleted, notification tables should be dropped.
* If the `Indexer::$listens_to` property forwards INSERT and UPDATE events to a single `saves` notification table rather than to two separate `inserts` and `updates` notification tables, old notification tables should be dropped, and the new one should be created.
* If an indexer is re-registered under new ID, old notification tables should be dropped, and new ones should be created.   

Lots of cases, huh?

### Invoking Notification Table Migrations

First, let's add notification table handling in schema migrations:

    // Osm\Admin\Schema\Diff\Schema
    /**
     * @var NotificationTable[]
     */
    protected array $notification_tables = [];

    /**
     * @var string[]
     */
    protected array $dropped_notification_tables = [];
    
    public function migrate(): void {
        ...
        foreach ($this->dropped_notification_tables as $table) {
            $this->drop($table);
        }

        foreach ($this->notification_tables as $table) {
            $table->migrate();
        }
    }

### Notification Table Migration Logic 

Then, let's move notification table migration logic to a new class:

    /**
     * @property Schema $schema
     * @property TableObject $source
     * @property Indexer $indexer
     * @property string $suffix
     * @property bool $cascade
     */
    class NotificationTable extends Diff
    {
        protected function get_schema(): Schema {
            throw new Required(__METHOD__);
        }
    
        protected function get_source(): Table {
            throw new Required(__METHOD__);
        }
        
        protected function get_indexer(): Indexer {
            throw new Required(__METHOD__);
        }
        
        protected function get_suffix(): string {
            throw new Required(__METHOD__);
        }
        
        protected function get_cascade(): bool {
            throw new Required(__METHOD__);
        }
        
        public function migrate(): void {
            $this->create();
        }
    
        protected function create(): void {
            $table = $this->indexer->getNotificationTableName($this->source, 
                $this->suffix);
                
            $this->db->create($table, function(Blueprint $table) {
                $table->integer('id')->unsigned()->unique();
    
                if ($this->cascade) {
                    $table->foreign('id')
                        ->references('id')
                        ->on($this->source->table_name)
                        ->onDelete('cascade');
                }
            });
        }
    }
    
