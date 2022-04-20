# Table Diff. Renaming Tables

Yesterday, I continued working on schema migrations:

* implemented table diff algorithm;
* took into consideration table renames.

Read more:

{{ toc }}

### meta.abstract

Yesterday, I continued working on schema migrations:

* implemented table diff algorithm;
* took into consideration table renames.

## `Table::diff()`

On a table level, everything depends on properties on the record class, and on indexers that listen to the changes in the given table. So, let's create empty CREATE/ALTER migrations and fill them in according to table properties and listeners. If some property fills in an ALTER migration, it will be executed, otherwise it will not:

    // Osm\Admin\Schema\Table
    public function diff(Migrators\Schema $migrator,
        \stdClass|Table|null $old): void
    {
        if ($old) {
            $migrator->alter_tables[] = $table = Migrators\Table\Alter::new([
                'table_name' => $this->table_name,
            ]);
            $migrator->alter_indexes[] = $index = Migrators\Index\Alter::new([
                'index_name' => $this->table_name,
            ]);
        }
        else {
            $migrator->create_tables[] = $table = Migrators\Table\Create::new([
                'table_name' => $this->table_name,
            ]);
            $migrator->create_indexes[] = $index = Migrators\Index\Create::new([
                'index_name' => $this->table_name,
            ]);
        }

        $migrator->create_notifications[] = $createNotifications =
            Migrators\Notification\Create::new([
                'table_name' => $table->table_name,
            ]);
        $migrator->drop_notifications[] = $dropNotifications =
            Migrators\Notification\Drop::new([
                'table_name' => $table->table_name,
            ]);

        foreach ($this->properties as $property) {
            throw new NotImplemented($this);
        }

        foreach ($this->listeners as $listener) {
            throw new NotImplemented($this);
        }
    }

## Renaming Tables

Talking about renaming tables, there are kind of renames: renaming a class, and renaming the database table.

### Renaming Table Class

Renaming a class is done using the `#[Rename]` attribute:

    // V1
    class Item extends Record {
    }
    
    // V2  
    #[Rename(Item::class)]
    class Product extends Record {
    }

This change doesn't affect database tables. However, the `Schema::diff()` needs to catch up:

    protected function diff(\stdClass|Schema|null $old): Migrators\Schema
    {
        ...
        foreach ($this->tables as $table) {
            if ($table->rename) {
                $name = $table->rename;
                if (!isset($old->tables->$name)) {
                    throw new InvalidRename(__(
                        "Previous schema doesn't contain the ':old_name' table referenced in the #[Rename] attribute of the ':new_name' table.",
                        ['old_name' => $table->rename, 'new_name' => $table->name]));
                }
            }
            else {
                $name = $table->name;
            }

            $table->diff($migrator, $old->tables->$name ?? null);
        }
        ...
    }

### Renaming Database Table

Renaming the underlying database table is done by changing `#[Table]` attribute:    

    // V1
    class Product extends Record {
    }
    
    // V2  
    #[Table('my_products')]
    class Product extends Record {
    }

**Notice**. If you don't use `#[Table]` attribute, the database table name may change after renaming/moving the table class, as in this case the database table name is inferred from the class name.

Renaming is "ordered" in the `Table::diff()`:

    public function diff(Migrator\Schema $migrator,
                         \stdClass|Table|null $old): void
    {
        if ($old) {
            ...
            if ($this->table_name !== $old->table_name) {
                $migrator->rename_tables[] = $table = Migrator\Table\Rename::new([
                    'old_table_name' => $old->table_name,
                    'table_name' => $this->table_name,
                ]);
                $migrator->rename_indexes[] = $index = Migrator\Index\Rename::new([
                    'old_index_name' => $old->table_name,
                    'index_name' => $this->table_name,
                ]);
                $migrator->rename_all_notifications[] = Migrator\Notification\RenameAll::new([
                    'old_table_name' => $old->table_name,
                    'table_name' => $table->table_name,
                ]);
            } 
        }
        ...
    }
