# Schema Migrations In Development Vs Production

Schema migrations are going to be generated and executed automatically under `gulp watch`. It will result in lots of tiny migrations: add a column, change its type, make it not nullable, and so on.

It's convenient during development, but it might be not optimal to reapply in production. Indeed, it's better to convert a large table to new structure all at once rather than running a conversion for each of the tiny migration.

This observation contradicts the [previously stated migration workflow](07-data-main-ideas-of-schema-change-migrations.md#migration-workflow), so let's update it.

Contents:

{{ toc }}

### meta.abstract

Schema migrations are going to be generated and executed automatically under `gulp watch`. It will result in lots of tiny migrations: add a column, change its type, make it not nullable, and so on.

It's convenient during development, but it might be not optimal to reapply in production. Indeed, it's better to convert a large table to new structure all at once rather than running a conversion for each of the tiny migration.

This observation contradicts the *previously stated migration workflow*, so let's update it.

## In Development, Run Migrations On Page Refresh

Let's say that during development you change schema here and there, and `gulp watch` runs migrations accordingly.

Some schema changes may cause trouble if processed automatically:
  
1. When schema version changes, the internal data format may change.
2. When you rename/remove a property, the migration may occur in the middle of the intended edit, for example if you switch to other window and then return.

For this reason, it's better to run migrations not on file change, but on page refresh in the browser (hitting `GET /products/`, `GET /products/create` or `GET /products/edit` routes).

**Note**. Running migrations under Web user credentials, it's better not to generate any source files due to possible insufficient permissions.

On a pro side, migrations may show warnings about possible unintended actions directly in the UI.

All executed migrations are stored in the database.

## Backups

If something doesn't go well, you can return to previous known "good" database backup by running `osm restore`. 

Create a backup using the `osm backup` command. 

It has the optional `name` argument:

    osm backup before_implementing_orders
    ...
    osm restore before_implementing_orders 

If there is no backup, `osm migrate:up --fresh && osm migrate:schema` drops everything from the database and runs module and schema migrations from the codebase.

If you unintentionally removed a property, changed schema version before implementing conversion of data stored in previous schema version, forgot to use the `#[RenamedFrom]` attribute while renaming a property, or other - restore the database from a backup.

## Saving Migrations In Codebase

Before deploying code to the server run the `osm generate:migrations` command that saves the migrations from the database to the `migrations/{app_name}` directory.

## Using Old Property Name Again

Let's say there had been the `property1` that you renamed to `property2`, and now you want to use the `property1` name for some new property:

    /**
     * @property string $property2 #[RenamedFrom('property1')]
     */
    class Product extends Record {
    } 
    
You can't just define `property1` - if you restore the database from backup and refresh a page in the browser, Osm Admin will try converting old `property1` to new definition and create new `property2`.

For this reason, Osm Admin doesn't allow defining a property mentioned in `#[RenamedFrom]` attributes - it's a syntax error.

Before removing the `#[RenamedFrom]` attribute, and defining `property1` anew, save migrations to the codebase.

The same goes with deleted properties. Before using a property name you have recently deleted, save migrations to the codebase.

   

