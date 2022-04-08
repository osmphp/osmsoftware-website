# Main Ideas Of Schema Change Migrations

The goal of the current iteration is to adjust the database schema and preserve/convert existing data according to *any* changes in schema classes, grids, forms or indexers.

Below are some thoughts how it might work with class definitions.

{{ toc }}

### meta.abstract

The goal of the current iteration is to adjust the database schema and preserve/convert existing data according to *any* changes in schema classes, grids, forms or indexers.

This post presents some thoughts how it might work with class definitions.


## Migration Types

### Adding/Removing Classes And Properties

These are quite trivial.

### Changing Property Type

Let's say we have class with one non-standard `qty` property telling how many items you have in stock:

    /**
     * @property int $qty
     */
    class Product extends Record {
    }
    
With time, you may discover that some products are measured in `kg`, and require a `float` quantity, for example, `1.5 kg`. So you change the definition: 

    /**
     * @property float $qty
     */
    class Product extends Record {
    }

If it's an implicit property, `_data.qty` should be converted in all existing objects.

If it's an explicit property, `qty` column definition should be changed instead - and MySql will handle the data conversion.

### Changing Property Explicitness

If you decide to make property explicit, that is, create a dedicated table column for its values:

    /**
     * @property int $qty #[Explicit]
     */
    class Product extends Record {
    }

Or you can convert it back to implicit:

    /**
     * @property int $qty
     */
    class Product extends Record {
    }
 
### Changing Property Nullability  

You may change property to be nullable or not: 

    /**
     * @property ?int $qty
     */
    class Product extends Record {
    }

Note that a property may be physically nullable even if it's not declared as such, for example, if it's computation formula depends on the object `id`.

### Changing Other Property Attributes

Other attribute changes may trigger database conversion as well:

    /**
     * @property int $qty #[Unsigned]
     */
    class Product extends Record {
    }

### Renaming Properties

You may change the internal property name:  

    /**
     * @property int $stock_qty #[Previously('qty')]
     */
    class Product extends Record {
    }

Remove the `Previously` attribute after finalizing the migrations. 

## No Data Losses

### Data Loss

Some data type conversions may result in a data loss. For example, when converting `string` to `int`, some values may fail to convert, and the default value will be used instead, most often, `null`. Osm Admin should warn about actual and potential data loss with all the details.

### Failed Conversion

Some data type conversions may fail. For example, when converting `string` to `int` that is non-nullable, and doesn't have a default value, Osm Admin would stop if any value fails to convert as there is nothing else to do. Failed conversion stops the whole process. Osm Admin should warn about potentially failing conversion.

Osm Admin should minimize failed conversions. For example, for a non-nullable column without a default value, it may use falsy value (empty string, zero or false).

### Dry Run

You can dry-run the migrations and see the warnings without turning the application down, then edit the data, and dry-run migrations again, until there are no warnings left. Then, you can put the system down and actually convert the database.

## Migration Workflow

### Same Migrations In Production

Migrations on a production server should run in exactly the same way as on your local machine. Osm Admin should "remember" all the migrations you ran on the local machine, and after pushing changes to the server it should them there.

It means that all the migrations should be under Git. There will be a command only used in development:

    osm generate:migrations
    
This command compares the schema defined in the codebase and the schema that is currently applied to the database, and creates migration files in the `migrations/{app_name}` directory:

    M000000001.php
    M000000002.php
    ...
    
The database also stores the last applied migration filename.

The `osm migrate:schema` runs all pending migrations.

On the development machine, use both commands. On the production server, use on the `osm migrate:schema` command. 

### Deployment Script

In case migration fails, the deployment script `bin/deploy.sh` should restore the production environment to the last good version of the codebase, the database and the search indexes.

For the codebase, it should remember the commit it is on, and restore to it if needed.

For the database, it should make a backup before running migrations, and restore from it in case of failure.

For the search indexes, it should remember all the search indexes altered during the migration, and recreate + reindex them if migrations fail. 

### Migration Script

On the local machine use `bin/migrate.sh` script to run the migrations, and restore the database and search indexes if it fails.

This script should be executed automatically in `gulp watch`.

### Application Protection

If the schema changes in the codebase, the application should not run until it's applied to the database.

### Mapping Version

Today, Osm Admin maps the schema to the database in one way. In the future it may change. It should detect schema mapping version in the codebase and convert the database and the search index to new format if it changes.