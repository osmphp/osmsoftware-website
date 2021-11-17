# Database

I've finished refactoring how Osm Admin stores data objects in the database. Also, in order to support multi-website, multi-vendor, multi-language applications, I've introduced the concept of *scopes*. 

Most reasoning from the [first version](02-data-migrations.md) is still valid, so let's take a fresh look of what's changed.

Contents:

{{ toc }}

### meta.abstract

I've finished refactoring how Osm Admin stores data objects in the database. Also, in order to support multi-website, multi-vendor, multi-language applications, I've introduced the concept of *scopes*.

Most reasoning from the *first version* is still valid, so let's take a fresh look of what's changed.

## Updated Object-Relational Mapping

### Tables

Use `#[Storage\Table]` attribute to create a table for the class instances, and use `#[Table\*]` attributes to create explicit columns for the class properties. All the rest properties will be stored as a JSON in the `data` column.

For example, let's model a user account class:

    use Osm\Admin\Base\Attributes\Table;
    use Osm\Admin\Base\Attributes\Storage;
    ...
    /**
     * @property int $id #[Serialized, Table\Increments]
     * @property string $email #[Serialized, Table\String_]
     * @property string $password #[Serialized]
     */
    #[Storage\Table('accounts')]
    class Account extends Object_
    {
    }

Given an account object:

    $account = Account::new([
        'id' => 1,
        'email' => 'john@doe.com',
        'password' => password_hash('{strong_password}', PASSWORD_ARGON2ID),
    ]);

A database record in the `accounts` table is:

    id  email           data           
    1   john@doe.com    { "password": "{password_hash}" }

### `id` Property

`id` property is so common that it's a good practice to use the `Id` trait instead of defining `id` property manually: 

    use Osm\Admin\Base\Traits\Id;
    ...
    /**
     * @property string $email #[Serialized, Table\String_]
     * @property string $password #[Serialized]
     */
    #[Storage\Table('accounts')]
    class Account extends Object_
    {
        use Id;
    }

### Scopes

In a multi-store setup of an e-commerce application, each store has its own, slightly customized copy of the product catalog. In database terms, each store operates on its own copy of the `products` table.

Similarly, in a multi-site CMS system, each site has its own copy of the `pages` table, for example, for translations.

The generic term for e-commerce stores and CMS sites is *scope*. Scopes are stored in `scopes` table:

    /**
     * @property ?int $parent_id #[
     *      Serialized,
     *      Table\Int_(unsigned: true, references: 'scopes.id', on_delete: 'cascade'),
     * ]
     * @property ?string $title #[Serialized]
     */
    #[Storage\Table('scopes')]
    class Scope extends Object_
    {
        use Id;
    }

Initially, there is one global scope in `scopes` table, and you can add more:

    id  parent_id   data
    1   null        {"title": "Global"}
    2   1           {"title": "English"}
    3   1           {"title": "German"}

### Scoped Tables

Use `#[Storage/ScopedTable]` attribute to create an additional table for the class instances in each scope:

    /**
     * @property string $sku #[Serialized, Table\String_]
     * @property string $description #[Serialized]
     */
    #[Storage\ScopedTable('products')]
    class Product extends Object_
    {
        use Id;
    }    

Given a product object:

    $product = Product::new([
        'id' => 1,
        'sku' => 'P12345',
        'description' => 'Lorem ipsum ...',
    ]);

The `id` of this object is stored in `products` table, and other properties are stored in scope-specific tables:

    products
    --------
    id  scope_id           
    1   1
    
    s1__products
    ------------
    id  sku     data
    1   P12345  {"description": "Lorem ipsum ..."}

    s2__products
    ------------
    id  sku     data
    1   P12345  {"description": "Once upon a time, ..."}

    s3__products
    ------------
    id  sku     data
    1   P12345  {"description": "Es war einmal ..."}

### Other Storage Types

Tables and scoped tables are just 2 examples of how class instances can be stored. 

In the future, there may be more, for example, `#[Storage\Array_]` attribute could mark objects that are retrieved from some in-memory array. 

It's worth noticing that not all data classes have their own storage. For example, product's volume discount objects defining "buy X, get discount Y" rules can be stored directly in the product's record.

## Updated Schema

Information about storages and columns, denoted using `#[Storage\*]` and `#[Table\*]` attributes is a part of schema. 

In the [previous article](15-data-schema-hydration.md), I've already presented the core models of the schema: classes and properties. Let's add tables and columns:

![Tables And Columns](database-tables.png)

As you can see, every `Class_` may contain a `Storage` object - either a `Table` or `ScopedTable`. Both `Table` or `ScopedTable` maintain an array of explicit `Column` objects. Each column "knows" about the property that serializes data into it.   

## Updated Migration Algorithm

### Creating Tables

Create database tables for the data classes by running a command:

    osm migrate:schema
    
Internally, this command invokes the `$app->schema->migrate()` method. 

This method creates all global tables by calling the `Storage::create()` method of every defined storage, and seeds them with minimum required data by calling `Storage::seed()` method.

At this stage, the global scope is created in the `scopes` table, and it triggers running `Schema::migrateScopeUp()` method which creates all scope-specific tables by calling `ScopedTable::createScope()` method.

Finally, the schema is dehydrated and saved into the `global_.schema` column.

### Adding Scopes

Each time you add a record to the `scopes` table, `Schema::migrateScopeUp()` method creates all scope-specific tables for the new scope.

### Adding New Tables And Columns

On subsequent run, `$app->schema->migrate()` method compares the schema defined using PHP attributes with the one saved in the `global_.schema` column, and only applies the changes: creates new tables, alters modifies existing tables, and drops obsolete tables both globally and in each scope.

 