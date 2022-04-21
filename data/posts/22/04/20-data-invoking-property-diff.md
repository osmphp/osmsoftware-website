# Invoking Property Diff

Before digging into property diff algorithm, let's dig into how exactly it's used, and what other property-related logic is there.

{{ toc }}

### meta.abstract

Before digging into property diff algorithm, let's dig into how exactly it's used, and what other property-related logic is there.

## Migrators

Depending on property settings, it may alter `$table` and `$index` migrators:

    public function diff(Migrator\Schema $migrator,
                         \stdClass|Table|null $old): void
    {
        if ($old) {
            $migrator->alter_tables[] = $table = Migrator\Table\Alter::new([
                'table_name' => $this->table_name,
            ]);
            $migrator->alter_indexes[] = $index = Migrator\Index\Alter::new([
                'index_name' => $this->table_name,
            ]);
            ...
        }
        else {
            $migrator->create_tables[] = $table = Migrator\Table\Create::new([
                'table_name' => $this->table_name,
            ]);
            $migrator->create_indexes[] = $index = Migrator\Index\Create::new([
                'index_name' => $this->table_name,
            ]);
        }
        ...

        foreach ($this->properties as $property) {
            throw new NotImplemented($this);
        }
        ...
    }

## Use Cases

As in `Schema::diff()`, there are four cases to handle:

1. adding new property
2. renaming existing property
3. modifying existing property
4. removing old property
  
Cases #1 and #3 are handled by calling `$property->diff()`. Case #4 requires an additional loop that goes through all old properties. 

## Renaming Properties

Case #2, renaming an existing property, is done using the same `#[Rename]` attribute as in table:

    // V1
    /**
     * @property int $qty
     */
    class Product extends Record {
    }
    
    // V2  
    /**
     * @property int $stock_qty #[Rename('qty')]
     */
    class Product extends Record {
    }

As in `Schema::diff()` rename handling is done before calling `$property->diff()`:

    foreach ($this->properties as $property) {
        if ($property->rename) {
            $name = $property->rename;
            if (!isset($old->properties->$name)) {
                if (isset($old->properties->{$property->name})) {
                    // once #[Rename] migrated, during another migration,
                    // "old" schema will already contain new name.
                    $name = $property->name;
                }
                else {
                    throw new InvalidRename(__(
                        "Previous schema of ':table' table doesn't contain the ':old_name' property referenced in the #[Rename] attribute of the ':new_name' property.", [
                            'table' => $this->name,
                            'old_name' => $property->rename, 
                            'new_name' => $property->name, 
                        ]
                    ));
                }
            }
        }
        else {
            $name = $property->name;
        }

        $property->diff($schema, $table, $index, $old->properties->$name ?? null);
    }

    if ($old) {
        foreach ($old->properties as $property) {
            if (isset($this->properties[$property->name])) {
                continue;
            }
            
            // drop table columns and index fields if they exist
            throw new NotImplemented($this);
        }
    }


