# Schema Fixtures

After drafting some ideas of how the schema migrations should actually work, let's start implementing it in TDD way.

{{ toc }}

### meta.abstract

After drafting some ideas of how the schema migrations should actually work, let's start implementing it in TDD way.

## Need For Schema Fixtures

Migration logic is non-trivial, it has to be reliable, and it's hard to test all edge cases manually - all these reasons indicate that it should be unit tested.

Unit tests should go like this: given old and new schema, generate and run migrations and check the results.

Currently, schema is tightly couples with data class definitions, and it's a problem for unit testing, as you can't have two versions of the same class in one codebase.

To resolve this problem, it should be possible to define multiple schemas in the codebase - I "real" schema, and many "test" schemas using `#[Schema]` attribute:

    #[Schema('old1')]
    class Test1\Old\Product extends Record {
    } 

    /**
     * @property string $color
     */
    #[Schema('new1')]
    class Test1\New_\Product extends Record {
    } 

This way, I can load both "old" and "new" schemas and compare them.

By the way, the "old" schema is dehydrated (or `null`), and only the "new" schema classes, properties, and so on are actually instantiated.

## First Schema Fixture

Testing schema migrations should be convenient. To achieve that, let's start from writing a sample schema fixture in a way that is convenient, and then, fix schema loading to support that.

The first sample schema consists of just one data class without any custom properties:

    <?php
    
    namespace Osm\Admin\Samples\Migrations\F1\V1;
    
    use Osm\Admin\Schema\Attributes\Fixture;
    use Osm\Admin\Schema\Record;
    
    #[Fixture]
    class Product extends Record
    {
    
    }

Note the `#[Fixture]` attribute:

* It removes the data class from actual schema.
* Instead, it's a part of `Osm\Admin\Samples\Migrations\F1` schema fixture (`F` means fixture).
* It's the first version of the schema fixture - `V1` says that (`V` means version).  


