# Subclasses

As described in [Migrations](02-data-migrations.md), most data objects of the same class will be stored in a database table.

But what about subclasses? In an e-commerce application, bags, dresses, and digital products, collectively known as subclasses, are all products stored in `products` table, and they may have bad-specific, dress-specific or digital product specific properties that should also be stored there.

This article describes how subclasses are defined and stored in Osm Admin.   

{{ toc }}

### meta.abstract

As described in *Migrations*, most data objects of the same class will be stored in a database table.

But what about subclasses? In an e-commerce application, bags, dresses, and digital products, collectively known as subclasses, are all products stored in `products` table, and they may have bad-specific, dress-specific or digital product specific properties that should also be stored there.

This article describes how subclasses are defined and stored in Osm Admin.   

## `type` Property

In the base class, `Product`, add `type` property using `Type` trait:

    use Osm\Admin\Base\Traits\Type;
    ...
    
    /**
     * @property string $sku #[Serialized]
     * @property string $description #[Serialized]
     */
    #[Table('products')]
    class Product extends Object_
    {
        use Id, Type;
    }

In child classes, assign the type property using `#[Type]` attribute:

    /**
     * @property string $color #[Serialized]
     */
    #[Type('bag')]
    class Bag extends Product {
    }  

    /**
     * @property string $color #[Serialized]
     * @property string $size #[Serialized]
     */
    #[Type('dress')]
    class Dress extends Product {
    }  

    /**
     * @property string $file #[Serialized]
     */
    #[Type('digital')]
    class Digital extends Product {
    }  

When the type property is serialized into a table record, it's computed from the `#[Type]` attribute. This way Osm Admin known what class to instantiate when loading data back from the database.

## Type-Specific Properties

In the above code sample, `color`, `size` and `file` are examples of type-specific properties. These properties are stored in the database table in the same way the `Product` class properties are stored: in the `data` JSON column, or, in case a `Column\*` attribute is specified, in a dedicated column.

In case you use the same property in several subclasses, make sure it has the same definition. For example, the `color` property is defined in both `Bag` and `Dress` class, and it has exactly the same definition. 

