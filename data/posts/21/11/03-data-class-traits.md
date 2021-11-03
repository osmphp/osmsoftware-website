# Data Class Traits

Different data classes have repeating structural patterns. For example, most data classes stored in database tables have the auto-incremented `id` property. Use PHP traits to effectively introduce the same properties to different data classes over and over again.

Osm Admin brings in several standard data class traits:

{{ toc }}

### meta.abstract

Different data classes have repeating structural patterns. For example, most data classes stored in database tables have the auto-incremented `id` property. Use PHP traits to effectively introduce the same properties to different data classes over and over again.

## `Id`

Instead of defining `id` property manually, use [`Id`](https://github.com/osmphp/data/blob/src/Tables/Traits/Id.php) trait:

    use Osm\Admin\Base\Traits\Id;
    ...

    /**
     * @property string $email #[Serialized]
     * @property string $password #[Serialized]
     */
    #[Table('accounts')]
    class Account extends Object_
    {
        use Id;
    }

The `Id` trait defines `id` property as follows:

    /**
     * @property int $id #[
     *      Serialized,
     *      Table\Increments
     * ]
     */
    trait Id
    {
    
    }