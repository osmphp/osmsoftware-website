# Optimizing Search Responses 

The implementation of the faceted implementation in `osm.software` blog has shown that sometimes you need only facet counts, or only total count of matching entries. However, currently, Osm Framework always returns total `count`, `ids`  and `facets`.

Let's fix that.

By default, the result will only contain `ids`. You can cancel that using `hits()` method:

    $query->hits(false);

If needed, request the total count using `count()` method:

    $query->count();

**Notice**. In previous framework version, `count()` method had different semantics. Search usages of this method in your code before migrating to `v0.15`.

### meta.abstract

The implementation of the faceted implementation in `osm.software` blog has shown that sometimes you need only facet counts, or only total count of matching entries. 

Until now, Osm Framework had always queried total `count`, `ids`  and `facets`. Not anymore - and the search engine only provides the information that is actually needed.

