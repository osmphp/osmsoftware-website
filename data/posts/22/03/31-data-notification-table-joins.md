# Notification Table Joins

Yesterday, I identified the need to have some syntax in a formula query to join a notification table.

I'm still working on it, but here is how it will look like:

    $query = query(Product::class);
    
    // INNER JOIN `zi9__products__inserts` 
    // ON `zi9__products__inserts`.`id` = `products`.`id` 
    $query->joinInsertNotifications($this, 'id');

    // INNER JOIN `zi9__products__updates` 
    // ON `zi9__products__updates`.`id` = `products`.`id` 
    $query->joinUpdateNotifications($this, 'id');
    

## It's Temporary Syntax

After implementing regular indexers, this syntax may change.

For example, let's consider `Category` objects:

    /**
     * @property ?Category $parent #[...]
     * @property string $title #[
     *      Computed("parent.parent.title ?? '' + ' - ' + parent.title ?? ''")
     * ]
     */
    class Category extends Record {
    } 
    
In this example, the 3rd level category title is combined from the titles of the 1st and the 2nd levels.

Whenever a category is updated, its ID is recorded into the `zi10__categories__updates` notification table.

The base regular indexer query should be something like this: 

    SELECT CONCAT(parent__parent.title, ' - ', parent.title)
    FROM categories
    LEFT OUTER JOIN categories AS parent 
        ON parent.id = categories.id
    LEFT OUTER JOIN categories AS parent__parent   
        ON parent__parent.id = parent.id

And at this point I'm not sure how the `zi10__categories__updates` notification table should be properly joined. I'll return to it later, but obviously regular indexing is a critical feature that should be done sooner than later.

### meta.abstract

Yesterday, I identified the need to have some syntax in a formula query to join a notification table.

I'm still working on new `Query::joinInsertNotifications()` and `Query::joinUpdateNotifications()` methods.
    