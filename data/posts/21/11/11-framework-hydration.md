# Hydration

Recently, I developed a couple of helper functions for transmitting PHP objects over the wire, and saving them in database records:

* `dehydrate()` - recursively converts an instance of a PHP class to a plain untyped object. Then, store the plain object in the database, or convert it to JSON and send it to a browser.
* `hydrate()` - recursively converts a plain untyped object back to a PHP class instance. Use if after decoding a JSON received from the browser, or after loading a database record.

This article describes how to use these functions.

{{ toc }} 

### meta.abstract

Recently, I developed a couple of helper functions for transmitting PHP objects over the wire, and saving them in database records:

* `dehydrate()` - recursively converts an instance of a PHP class to a plain untyped object. Then, store the plain object in the database, or convert it to JSON and send it to a browser.
* `hydrate()` - recursively converts a plain untyped object back to a PHP class instance. Use if after decoding a JSON received from the browser, or after loading a database record.

This article describes how to use these functions.

## Usage

Let's say, that your application deals with sales orders each having multiple lines:

    $order = Order::new(['no' => 'ORDER-001']);
    $order->lines = [
        Line::new([
            'order' => $order,
            'title' => 'Product 1',
            'price' => '5.0',
            'qty' => 1,
        ]),
        Line::new([
            'order' => $order,
            'title' => 'Product 2',
            'price' => '10.0',
            'qty' => 5,
        ]),
    ];

Convert the `$order` to a plain untyped object, and send it to the browser using the `dehydrate()` function:

    use function Osm\dehydrate;
    ...
    $json = json_encode(dehydrate($order));

After receiving an order JSON from the browser, convert it back to an `Order` instance using the `hydrate()` function:

    use function Osm\dehydrate;
    ...
    $order = hydrate(Order::class, json_decode($json));

## Preparing Classes For Hydration

How to be sure that your objects dehydrate and hydrate properly? Let's start with correct definitions of the `Order` and `Line`  classes, and then review important implementation details:

    use Osm\Core\Object_;
    use Osm\Core\Attributes\Serialized;
    use Osm\Framework\Db\Db;
    
    /**
     * @property string $no #[Serialized]
     * @property Line[] $lines #[Serialized]
     * @property Db $db
     */
    class Order extends Object_ {
        ...
        public function __wakeup(): void {
            foreach ($this->lines as $line) {
                $line->order = $this;
            }
        }
    }
    
    /**
     * @property Order $order
     * @property string $title #[Serialized]
     * @property float $price #[Serialized]
     * @property int $qty #[Serialized]
     */
    class Line extends Object_ {
        ...
    }

## `Serialized` Attribute

Mark your properties with the `#[Serialized]` attribute, as the `dehydrate()` function removes all the other properties from the dehydrated object.

Not all properties should be serialized:

* dependency properties, such as `Order::$db`, and other properties of computed nature, should not be serialized. Instead, these properties are re-evaluated after hydrating back by running their property getters.
* parent references, such as `Line::$order`, can't be serialized, either. Instead, re-assign these properties in the [`__wakeup()`](#__wakeup-method) method of the parent object.

## `__wakeup()` Method  

As stated above, parent references such as `Line::$order`, can't be serialized. It means that you dehydrate an object, and then hydrate it back, such properties won't be restored.

Re-assign these properties manually in the `__wakeup()` method of their parent object. For example, the `Order` object, restores parent references of its `Line` objects as follows:

    public function __wakeup(): void {
        foreach ($this->lines as $line) {
            $line->order = $this;
        }
    }

## `SubTypes` Trait And `Type` Attribute 

In case a dehydrated object may be of a derived class, use `SubTypes` trait in the base class, and `#[Type]` attribute in the derived class. 

For example, in case order lines about sold products and services differ in structure and logic, define PHP classes as follows:

    use Osm\Core\Traits\SubTypes;
    use Osm\Core\Attributes\Type;
    ...
    
    class Line extends Object_ {
        use SubTypes;
        ...
    }

    #[Type('product')]
    class ProductLine extends Line {
        ...
    }

    #[Type('service')]
    class ServiceLine extends Line {
        ...
    }
        

## Testing

If you dehydrate an object and then hydrate it back, you get an exact copy of the original object. 

For complex object trees, write unit tests for complex object trees to make sure that `#[Serialized]` attributes and `__wakeup()` methods work as expected. 

