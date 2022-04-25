# Search Index Considerations. Better Diff Syntax

This time:

* I found out that the whole search index creation should be done during indexing, not migrations.
* I refactored diff algorithm and made it much easier to read.

{{ toc }}

### meta.abstract

This time:

* I found out that the whole search index creation should be done during indexing, not migrations.
* I refactored diff algorithm and made it much easier to read.

## `Property::diff()` Stub

Let's start the `Property::diff()` algorithm with a method stub:

    public function diff(Migrator\Schema $schema,
        Migrator\Table\Create|Migrator\Table\Alter $table,
        Migrator\Index\Create|Migrator\Index\Alter $index,
        \stdClass|Property|null $old)
    {
        throw new NotImplemented($this);
    }

## Search Index Considerations

### Rebuilding Index On Structure Change

I remembered that Osm Framework doesn't have an `alter()` method. The idea is that if search index structure changes, you should rebuild it from scratch. 

It means that there will be no `Index\Alter` migrator class. Instead, `Index\Create` will have `drop_if_exists` flag.

Fixed that.

### If Migration Fails, Index Must Be Rebuilt

If migration fails, you have to restore the last "good" version of the database from a backup.

The restored database may even "know" that search indexes are up-to-date. And it's a problem, as failed migration process may leave search indexes half-baked.

To fix that, `osm restore` should mark all search indexes as requiring full reindex. By the way, if an application syncs its data to other 3rd party services, `osm restore` should mark such sync links as requiring full update, too.    

This reasoning has three implications:

1. Search index creation should be a part of indexing. During full reindex,  the indexer should create the index and fill it with data. During partial reindex, it should only update its data.
2. Migration should not care about search indexes at all.
3. Application logic should check if a search index requires full reindex, and if so, it should not hit ElasticSearch at all. Instead, it should provide alternative query processing by DB means only.

Now, as I implement migrations, it means that all search index stuff has to be removed. However, after a successful migration: 

* A search index with modified structure should be dropped and marked as requiring full reindex.
* The dropping logic should take renaming into account.

## Better Diff Syntax

I've noticed that the migrator tree is too detailed, and it's maintenance takes too much space in diff algorithm. Let's refactor it, here is how `diff()` methods look like now:

    // Osm\Admin\Schema\Schema
    public function diff(Migrator\Schema $schema): void
    {
        foreach ($this->tables as $table) {
            $tableMigrator = $schema->table($table);
            $table->diff($tableMigrator);
        }

        if ($schema->old) {
            foreach ($schema->old->tables as $table) {
                $this->planDeletingTable($schema, $table);
            }
        }
    }

    // Osm\Admin\Schema\Table
    public function diff(Migrator\Table $table): void {
        $table->alter = $table->old != null;
        $table->rename = $table->old &&
            $table->new->table_name !== $table->old->table_name
                ? $table->old->table_name
                : null;

        foreach ($this->properties as $property) {
            $property->diff($table->property($property));
        }

        if ($table->old) {
            foreach ($table->old->properties as $property) {
                $this->planDeletingProperty($table, $property);
            }
        }
    }

Much, much shorter.
