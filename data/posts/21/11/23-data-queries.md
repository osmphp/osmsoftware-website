# Queries

This article is about running queries on data objects stored in the database.  

In short, use the `query()` function. It runs on top of Laravel [`Query\Builder`](https://laravel.com/docs/queries). It handles the [mapping of data class properties onto table columns](17-data-database.md), and initiates the computation of indexed properties. 

Currently, you can only SELECT and INSERT, other operations are coming soon.

Let's begin:

{{ toc }}

### meta.abstract

This article is about running SELECT and INSERT queries on data objects stored in the database.

In short, use the `query()` function. It runs on top of Laravel `Query\Builder`. It handles the *mapping of data class properties onto table columns*, and initiates the computation of indexed properties. 

Currently, you can only SELECT and INSERT, other operations are coming soon.

## Introduction

Use `query()` function to SELECT, INSERT, UPDATE or DELETE data objects from a database table:

    use function Osm\query;
    ...
    $order = query(Order::class)->raw(...)->first();
    $id = query(Order::class)->insert([...]);
    query(Order::class)->raw(...)->update([...]);
    query(Order::class)->raw(...)->delete();

## Configuring Internal Laravel Query

Internally, a [Laravel query](https://laravel.com/docs/queries) does all the heavy lifting. Configure it (except columns) using the fluent `raw()` method:

    use Illuminate\Database\Query\Builder as QueryBuilder;
    ...
    $order = query(Order::class)
        ->raw(fn(QueryBuilder $q) => $q->where($id), 1)
        ->first();

Alternatively, use the `raw` property:

    $query = query(Order::class);
    $query->raw->where('id', 1);
    $order = $query->first();    

## Selecting Properties

The subtle difference between the query object returned by the `query()` function and the internal Laravel query is that the latter selects, inserts and updates table columns, while the former selects, inserts and updates class properties. It's important as some class properties don't have dedicated columns and are stored in a big fat JSON `data` column. 

For example, a `Scope` object is stored in `scopes` table like this:

    id  parent_id   level   id_path data
    1   null        0       1       {"title": "Global"}

So, don't use `query()->raw->select()`. Instead, use `query()->select()`:

    $order = query(Scope::class)
        ->select(['id', 'title'])
        ->first();  

Internally, after retrieving a raw table record the `query()` also decodes the `data` JSON column, and extracts requested properties from there, too.

If `select()` method is omitted, all properties are selected. You can also explicitly select all properties using `*`:

    $scopes = query(Scope::class)
        ->select(['*'])
        ->first();  

## Retrieving Data

Use the `get()` method to execute the SELECT statement and retrieve all matching objects having the selected properties:

    $result = query(Scope::class)->get();
    
Optionally, pass the array of selected properties:

    $result = query(Scope::class)->get(['id', 'title']);
 
Either way, the query returns a `Result` object. This object's the most important property is `items`. It contains the matching objects with all the selected properties. I'll return to other properties of the `Result` object later.

Use the `first()` method to retrieve the first matching object, or `null`. Again, you can optionally pass the array of selected properties:

    $scope = query(Scope::class)->first(['id', 'title']);

## Hydrating Retrieved Objects

By default, the `get()` and `first()` methods return plain untyped objects. You can hydrate the result into the data class instances using the `hydrate()` method: 

    /* @var Scope $scope */
    $scope = query(Scope::class)
        ->hydrate()
        ->first(['id', 'title']);

## Inserting Objects

Use `insert()` method to insert an object into the table and get its ID:

    $id = query(Scope::class)->insert((object)[
        'title' => __('Global'),
    ]);    
    
Alternatively, you can pass an array of property values:

    $id = query(Scope::class)->insert([
        'title' => __('Global'),
    ]);    

To insert a PHP class instance, use `dehydrate()` function:

    $scope = Scope::new(['title' => __('Global')]);
    $id = query(Scope::class)->insert(dehydrate($scope));