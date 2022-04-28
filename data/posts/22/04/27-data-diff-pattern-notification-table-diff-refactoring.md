# Diff Pattern. Notification Table Diff. Refactoring

While working on diff algorithm I noticed a certain pattern that I use over an over again.

Using this pattern, I implemented notification table diff.

Finally, I rearranged diff/migration code for better code readability.

Here it how it went:

{{ toc }}

### meta.abstract

While working on diff algorithm I noticed a certain pattern that I use over an over again.

Using this pattern, I implemented notification table diff.

Finally, I rearranged diff/migration code for better code readability.

## Diff Pattern

I noticed some pattern in what diff algorithm "naturally" boils to: it's in its input and output. 

Why is it important? Now, after noticing this pattern, I can apply it to the parts of the diff algorithm that are not written yet, starting with notification table comparison. 

### Output 

Let's start with the output.

**Example**. After comparing database tables in the old and the new schema, the diff returns two arrays:

* `tables` - an array of table diff objects telling which tables should be in the database after migrating to the new schema;
* `dropped_tables` - an array of table names that should be dropped from the database.

In similar fashion, property diffing returns `properties` and `dropped_properties`.

The second result, for example `dropped_tables`, has a lot simpler structure than the first one, `tables`. You don't need much information to drop a table. On the contrary, to make sure a table exists, you need to now all the details to create it, you need to know if it's already there, and if so, how it's currently defined.    

### Input

Another thing is input.

**Example**. The table diff algorithm compares two table arrays: the old one, and the new one. Both arrays use table class name as array key, as it uniquely identifies a table. Also, the new table object contains enough information (the `rename` property) to find the old table if the unique key changes.

The same is applicable to other parts of diff algorithm, too. There are:

* old and new arrays;
* accessed by some unique key;
* having information about unique key changes.   

## Notification Table Diff

Following the [pattern](#diff-pattern), there should be a serialized `Schema::$notification_tables` array.

Its key should contain the source table class name, the indexer ID, and the notification suffix.

Diff algorithm should still find old notification table definition is the source table class name changes, and it should initiate renaming if the source table name changes. 

Other changes - renaming the notification suffix, subscribing to another set of table events, toggling cascade deletes should result in dropping the old notification table, creating the new one, and requesting full reindexing.

### `Schema::$notification_tables`

Let's collect and serialize the notification table array:

    // Osm\Admin\Schema\Schema
    protected function get_notification_tables(): array {
        $tables = [];

        foreach ($this->tables as $table) {
            foreach ($table->listeners as $indexer) {
                $listensTo = $indexer->listens_to[$table->name];

                if ($inserted = $listensTo[Query::INSERTED] ?? null) {
                    $this->registerNotificationTable($tables, $table,
                        $indexer, $inserted, cascade: true);
                }

                if (($updated = $listensTo[Query::UPDATED] ?? null) &&
                    $updated != $inserted)
                {
                    $this->registerNotificationTable($tables, $table,
                        $indexer, $updated, cascade: true);
                }

                if ($deleted = $listensTo[Query::DELETED] ?? null) {
                    $this->registerNotificationTable($tables, $table,
                        $indexer, $deleted, cascade: false);
                }
            }
        }

        return $tables;
    }

    protected function registerNotificationTable(array &$tables, Table $table,
        Indexer $indexer, mixed $suffix, bool $cascade): void
    {
        $notificationTable = NotificationTable::new([
            'schema' => $this,
            'table_name' => $table->name,
            'table' => $table,
            'indexer_id' => $indexer->id,
            'indexer' => $indexer,
            'suffix' => $suffix,
            'cascade' => $cascade,
        ]);

        $tables[$notificationTable->name] = $notificationTable;
    }

### `Schema::diff()`

Now, I have `Schema::$notification_tables` as input, and `Diff\Schema::$notification_tables` and `Diff\Schema::$dropped_notification_tables` as output. 

It's time to implement diff algorithm:

    // Osm\Admin\Schema\Schema
    public function diff(Diff\Schema $schema): void
    {
        ...
        foreach ($this->notification_tables as $table) {
            $table->diff($schema->notificationTable($table));
        }

        if ($schema->old) {
            foreach ($schema->old->notification_tables as $table) {
                $this->planDeletingNotificationTable($schema, $table);
            }
        }
    }

    // Osm\Admin\Schema\NotificationTable
    public function diff(Diff\NotificationTable $notificationTable): void
    {
        throw new NotImplemented($this);
    }

    // Osm\Admin\Schema\Diff\Schema
    public function notificationTable(NotificationTableObject $table)
        : NotificationTable
    {
        throw new NotImplemented($this);
    }

    // Osm\Admin\Schema\Schema
    protected function planDeletingNotificationTable(Diff\Schema $schema,
        \stdClass|NotificationTable $table): void
    {
        //throw new NotImplemented($this);
    }

### `Diff\Schema::notificationTable()`

**Later**. Out of the three unimplemented methods, for now, the `planDeletingNotificationTable()` method remains such.

The `notificationTable()` method resolves table `#[Rename]` attribute, creates notification table diff object, or returns already existing one:

    public function notificationTable(NotificationTableObject $table)
        : NotificationTable
    {
        if (!isset($this->notification_tables[$table->name])) {
            if ($table->rename) {
                $name = $table->rename;
                if (!isset($this->old->notification_tables->$name)) {
                    $name = $table->name;
                }
            }
            else {
                $name = $table->name;
            }

            $this->notification_tables[$table->name] = NotificationTable::new([
                'old' => $this->old->notification_tables->$name ?? null,
                'new' => $table,
                'schema' => $this,
                'output' => $this->output,
                'dry_run' => $this->dry_run,
            ]);
        }

        return $this->notification_tables[$table->name];
    }

### `NotificationTable::diff()`

**Later**. I'll return to this method soon, when I actually have a fixture requiring to do something with existing notification tables.

### Result

As the result, the first test that doesn't have any "old" schema, works!

## Refactoring

Before continuing, let's move all diff and migration logic to `Diff` classes. Done.

