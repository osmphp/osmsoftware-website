# Concepts

**Notice**. This is a live document. I've just started working on this project, and I try different ideas. The current state of the project is presented below. It's likely to change, in this case I'll update this article, too.

This article introduces the core project concepts: data object, data class, data query, and data schema.

Contents:

{{ toc }}

### meta.abstract

This article introduces the core project concepts: data object, data class, data query, and data schema.

## Objects 

OK, let's begin.

It's hard to reason about some abstract data-intensive application, so instead, let's model an e-commerce application. As someone recently commented on Twitter, e-commerce is about money, and I can't agree more. However, from a technical standpoint, it's about entering data in the application admin area, and rendering it in the application front area.

One typical piece of data in the e-commerce application is products. Below is a couple of sample products that you can retrieve via the HTTP API, or visualize in the admin area:

    [
        {
            "id": 1,
            "sku": "product1",
            "title": "Product 1",
            "description": "Lorem ipsum ...",
            "price": 5.0,
            "related_products": [
                {
                    "id": 1,
                    "product_id": 2
                }
            ]
        },
        {
            "id": 2,
            "sku": "product2",
            "title": "Product 2",
            "description": "Lorem ipsum ...",
            "price": 10.0,
            "related_products": [
                {
                    "id": 2,
                    "product_id": 1
                }
            ]
        }
    ]  

Note that, a single product object may contain properties of simple, object, or an array type. It means that when loading a product from the database, it might need multiple `SELECT` statements to construct. I'll look into the database logic later. For now, keep in mind that it's going to be more complicated than a single `SELECT`.

## Classes

Data objects can be modeled using PHP classes. Let's take the products from the previous example:

    use Osm\Core\Object_;
    use Osm\Core\Attributes\Serialized;

    /**
     * @property int $id #[Serialized]
     * @property string $sku #[Serialized]
     * @property string $title #[Serialized]
     * @property string $description #[Serialized]
     * @property float $price #[Serialized]
     * @property RelatedProduct[] $related_products #[Serialized]
     */
    class Product extends Object_ {
    }
    
    /**
     * @property int $id #[Serialized]
     * @property int $product_id #[Serialized]
     */
    class RelatedProduct extends Object_ {
    }
    
## Queries

Data objects can be retrieved from the database, or other data source, using queries. Each separately stored data class (for example, in a database table) will have its own query class.By convention, its name is a plural form of its data class:

    #[Of(Product::class)]
    class Products extends Query {
    }
    
    #[Of(RelatedProduct::class)]
    class RelatedProducts extends Query {
    }
    
A query class is bound to the matching data class using `#[Of]` attribute.

Queries will have methods similar to Laravel query builder. Internally, they'll combine database queries, and [search queries](https://osm.software/docs/framework/processing-data/search.html) to retrieve data objects, total object count, and faceted data. For more details, check a [working reference implementation](../06/28-osmsoftware-search-and-layered-navigation.md).  

For example:

    $result = Products::new()->where('price', '>', 5)->get(['id', 'sku']);
    
## Schema

Internally, queries need detailed information about the structure of the data objects - the data *schema*. The schema describes all classes and properties that queries can work with:

    /**
     * @property Class_[] $classes #[Serialized]
     */
    class Schema extends Object_ {
    }

    /**
     * @property Property[] $properties #[Serialized]
     * ...
     */
    class Class_ extends Object_ {
    }
    /**
     * ...
     */
    class Property extends Object_ {
    }

You can get the schema information using the `$osm_app->schema` property:

    global $osm_app; /* @var App $osm_app */
    
    $productClass = $osm_app->schema->classes[Product::class];