# Migrations

Recently, I partly implemented data schema migration. It takes data class definitions, and incrementally creates or alters underlying database tables.

This article describes how schema migration works, and what's not implemented yet, but most probably will be.

**Note**. This topic is continued in [the new article](17-data-database.md).   

{{ toc }} 

### meta.abstract

I partly implemented data schema migration. It takes data class definitions, and incrementally creates or alters underlying database tables.

The article below describes how schema migration works, and what's not implemented yet, but most probably will be.

**Note**. This topic is continued in the *new article*.

## Running Schema Migration

Later, there will be a command that will make all pending changes to the database tables:

    osm migrate:schema

Currently, the command is not there yet. However, the same logic is executed in the bootstrapping code of the main test suite, `tests/bootstrap.php`:

    Apps::run(Apps::create(App::class), function(App $app) {
        ...
        $app->schema->migrate();
    });

## How It Works

On the first run, the `osm migrate:schema` command creates new tables and columns there. 

Currently, it's the only implemented use-case. It means that for now, the database should be cleared out and migrated anew.

Later, the `osm migrate:schema` command will save the schema into the database, and on a subsequent run, it will compare the schema that is currently defined in code with the last migrated schema, and it will only apply the changes to the database tables.

## Class-Table Mapping

But how a data class is mapped on to a table, exactly?

Here are my thoughts:

* On one hand, a single record should be able to store unlimited number of data object attributes. This requirement eliminates the obvious option of storing each attribute in its own column, as there are database engine limitations. For example, in MySql, you can have only about 80 columns of `varchar (255)` type.
* On the other hand, filtering and sorting should be fast and use table indexes, the referential integrity is also a must. Hence, properties that participate in filtering, sorting or referential integrity should be stored in dedicated columns.    

So, I chose mixed mapping. The really important properties will be stored in dedicated table columns, and the rest properties will be put into an additional column of JSON type.

## Example

For example, consider a user account class:

    /**
     * @property int $id #[
     *      Serialized,
     *      Column\Increments
     * ]
     * @property string $email #[Serialized]
     * @property string $password #[Serialized]
     */
    #[Table('accounts')]
    class Account extends Object_
    {
    }

Applied attributes, or lack of them, affect the database structure:

* The `#[Table]` attribute specifies that objects of this class will be stored in the `accounts` table. 
* The `#[Column\Increments]` attribute explicitly maps the `id` property onto an auto-increment unsigned integer `id` column.
* Other serialized attributes are stored in the additional `data` column of JSON type. 

Given an account object: 

    $account = Account::new([
        'id' => 1,
        'email' => 'john@doe.com',
        'password' => password_hash('{strong_password}', PASSWORD_ARGON2ID),
    ]);

A database record is:

    id  data           
    1   { "email": "john@doe.com", "password": "{password_hash}" }

If the application logic requires the search by the `email` column, add `Column\*` attribute to the `email` property in the data class definition:

    /**
     * ...
     * @property string $email #[
     *      Serialized,
     *      Column\String_,
     * ]
     * ...
     */
    #[Table('accounts')]
    class Account extends Object_
    {
    }

In this case, a database record would be

    id  email           data           
    1   john@doe.com    { "password": "{password_hash}" }

## `Column\*` Attributes

Internally, every `Column\*` attribute is translated into matching [Laravel schema builder](https://laravel.com/docs/migrations#creating-columns) method.

For example, `Column\Increments` becomes

    $this->db->create('accounts', function (Blueprint $table) {
        $table->increments('id');
    });   

Currently, I have only implemented `Increments` and `Int_` attributes, but with time I plan to add all the rest.

