# Search

{{ toc }}

## meta

    {
        "tags": ["Framework documentation"],
        "category": "framework"
    }

## meta.list_text

Full-text search and faceted navigation is a common feature for e-commerce
applications. SQL solutions are often not optimal, and dedicated search engines,
such as ElasticSearch or Algolia, are used instead.

On the other hand, search and facets are useful not only for browsing a product
catalog. In fact, any non-trivial data set benefits from it.

The `osmphp/framework` package provides a unified set of classes for putting
data into a search index and searching over it. As an example, do consider an
e-shop product catalog, although it works with any data set.

## Introduction

Full-text search and faceted navigation is a common feature for e-commerce applications. SQL solutions are often not optimal, and dedicated search engines, such as ElasticSearch or Algolia, are used instead.

On the other hand, search and facets are useful not only for browsing a product catalog. In fact, any non-trivial data set benefits from it.

The `osmphp/framework` package provides a unified set of classes for putting data into a search index and searching over it. As an example, do consider an e-shop product catalog, although it works with any data set.

## Configuration

### ElasticSearch

Before using search capabilities, configure what search engine you'll use, and specify its connection settings in `settings.{{ app_name }}.php`:

    ...
    return (object)[
        ...
        'search' => [
            'driver' => 'elastic',
            'index_prefix' => $_ENV['SEARCH_INDEX_PREFIX'],
            'hosts' => [
                $_ENV['ELASTIC_HOST'] ?? 'localhost:9200',
            ],
            'retries' => 2,
        ],
    ];  
    
The example above refers to the ElasticSearch installed on a local machine. For all the settings, consult [ElasticSearch documentation](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/configuration.html).

The configuration above uses some environment variables, define them in `.env.{{ app_name }}`:

    NAME=osmcommerce
    ...
    SEARCH_INDEX_PREFIX="${NAME}_"

### Algolia

Alternatively, you may use Algolia. 

1. After creating an account on <https://www.algolia.com/>, use the following configuration to `settings.{{ app_name }}.php`:

        ...
        return (object)[
            ...
            'search' => [
                'driver' => 'algolia',
                'index_prefix' => $_ENV['SEARCH_INDEX_PREFIX'],
                'app_id' => $_ENV['ALGOLIA_APP_ID'],
                'admin_api_key' => $_ENV['ALGOLIA_ADMIN_API_KEY'],
            ],
        ]; 
    
2. Assign referenced environment variables in `.env.{{ app_name }}`:

        NAME=osmcommerce
        ...
        SEARCH_INDEX_PREFIX="${NAME}_"
        ALGOLIA_APP_ID=...
        ALGOLIA_ADMIN_API_KEY=... 
        
## Creating indexes

An index in a search engine is somewhat similar to a database table. First you create, then you fill it in with data, then you make queries from it. Finally, if it's no longer needed, you drop it. Use the following methods for creating/dropping indexes:

    // create an index
    $osm_app->search->create('products', function(Blueprint $index) {
        $index->string('sku');
        $index->int('qty');
    });
    
    // check if an index exists
    if ($osm_app->search->exists('products')) {
        ...
    }
    
    // drop an index
    $osm_app->search->drop('products');

**Note**. If you are familiar with Laravel, they should remind you the Laravel schema builder. 

### Field types

Use the following field types:

    $index->string('sku');
    $index->int('qty');
    $index->float('price');
    $index->bool('in_stock');

You may allow a field to have multiple values by using plural type names:

    $index->strings('tags');
    $index->ints('color_ids');
    $index->floats('widths');

### Field attributes

Enable fields to be used in search, filtering, facet counting, and sorting, and specify related settings:

    // use a field in full-text search, and specify its weight and order
    $index->string('text')->searchable(
        weight: 2.0, // by default, 1.0
        order: 1, // by default, not specified     
    );
    
    $index->string('sku')->filterable();
    $index->string('sku')->faceted(max_items: 500);
    $index->float('price')->sortable();
    
### Engine-specific index settings

The underlying engines have more features to configure, and with time the described API will cover most of them. If you need those features right now, configure them by adding engine-specific logic:

    // modify ElasticSearch index creation request
    $index->on('elastic:creating', fn($request) => merge($request, [
        'settings' => [
            'index' => [
                'number_of_shards' => 2, 
            ],        
        ],    
    ]);  

    // do things after an ElasticSearch index is created
    $index->on('elastic:created', function() use ($index) {
        $index->search->client->...
    });  

    // modify Algolia index settings
    $index->on('algolia:creating', fn($request) => merge($request, [
        'customRanking' => ['desc(followers)']
    ]);  

    // do things after an Algolia index is created
    $index->on('algolia:created', function() use ($index) {
        $index->index()->...
    });  

See also: 

* <https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-create-index.html>
* <https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/>
* <https://www.algolia.com/doc/api-reference/api-methods/set-settings/>
* <https://www.algolia.com/doc/api-reference/api-methods/>

## Adding data to a search index

Use SQL-like `insert()`, `update()` and `delete()` methods of the index query object to manage data in an index:

    $osm_app->search->index('products')->insert([
        'id' => 5,
        'sku' => 'P123',
    ]);
    
    $osm_app->search->index('products')->update(5, [
        'price' => 9.99,
    ]);
    
    $osm_app->search->index('products')->delete(5);
    
### `id` field

`id` field is implicitly defined in every index, and internally, it is used as a unique
document identifier. Always provide `id` value in the `insert()`, and use the same value in `update()` and `delete()`.

### Changes are not instant

Search engines don't wait for an operation to actually happen, and instead, they queue it and return control to your code immediately. It means that if query the index just after making changed to it, the changes won't be returned right away.

In most cases, it's a good thing, but not in unit tests. For this reason, consider enforcing the waiting for the end of each operation in the search engine connection settings:   

    // ElasticSearch
    'search' => [
        ...
        'refresh' => true,
    ],

    // Algolia 
    'search' => [
        ...
        'wait' => true,
    ],

However, it will make your code slower, so don't use these flags in production.

## Querying a search index

### Search queries return IDs

Search index queries return IDs of matching records, so after querying the search index, populate the actual data from the database using the `whereIn()` method:

    // 1. get IDs from the search index
    $ids = $osm_app->search->index('products')
        ->search('yellow jacket')
        ->ids();
    
    // 2. populate data from the database
    $items = $osm_app->db->table('products')
        ->whereIn('id', $ids)
        ->get(['sku', 'title', 'price']);
        
### Applying filters

    $ids = $osm_app->search->index('products')
        // request full-text search
        ->search('yellow jacket')

        // term filters
        ->where('tags', '=', 'technical-article')
        ->where('category_ids', 'in', [5, 10, 15])

        // range filters
        ->where('price', '>=', 5.0)                
        ->where('price', '<=', 15.0)
        
        // multiple range filters
        ->whereOr(fn(WhereClause $clause) => $clause
            ->whereAnd(fn(WhereClause $clause) => $clause
                ->where('weight', '>=', 1.0)                
                ->where('weight', '<', 2.0)
            )
            ->whereAnd(fn(WhereClause $clause) => $clause
                ->where('weight', '>=', 5.0)                
            )
        )                
        
        // run the query
        ->ids();

### Getting faceted counts and stats

    $result = $osm_app->search->index('products')
        // apply filters
        ...

        // term counts
        ->facetBy('tags')        
        ->facetBy('category_ids')        

        // min and max values
        ->facetBy('price', min: true, max: true)        

        // skip counting                
        ->facetBy('weight', min: true, count: false)        

        // run the query
        ->get();

    $ids = $result->ids;
    $minPrice = $result->price->min;
    $tagCounts = $result->tags->items;
    
### Sorting and paging

    $result = $osm_app->search->index('products')
        // apply filters
        ...

        ->orderBy('price', desc: true)
        ->offset(20)
        ->limit(10)
        
        // run the query
        ->ids();
    