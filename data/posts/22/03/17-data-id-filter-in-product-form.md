# `id` Filter In Product Form

In the product editing form, I implemented the `id` filter, and made the UI query work in database-only mode, too.

More details:
 
{{ toc }}

### meta.abstract

In the product editing form, I implemented the `id` filter, and made the UI query work in database-only mode, too.

## Applying `id` Filter

Let's begin with `/products/edit?id=1`. I'd already made some effort earlier, and I'd put it on pause as it was not clear how URL query parsing should work on the editing page. 

Now, the list page already parses URL query, for example `?color=red+blue`, and the `?id=1+2+3` on filter the editing page should work the same way.

Again, the entry point here is [`Osm\Admin\Ui\Query::fromUrl()` method](16-data-rendering-facets-and-applying-filters.md#parsing-http-parameters). For `id` filter, it calls `parseUrlFilter()` method, which, in turn, calls if property `index_filterable` is set, calls property's `parseUrlFilter()` method.

And the `index_filterable` flag of the `id` property is not set. Currently, it's set if a property is shown in faceted navigation or as a column in the object grid:

    protected function get_index_filterable(): bool {
        if ($this->faceted) {
            return true;
        }

        foreach ($this->parent->list_views as $list) {
            if ($list->filterable($this->name)) {
                return true;
            }
        }

        return false;
    }

Adding here an additional rule for just `id` property is not right. For example, there will be only one `settings` object, and it doesn't make sense to allow filtering its HTTP routes by `id`. However, I can also check if the object is `singleton`:

    if ($this->name == 'id' && !$this->parent->singleton) {
        return true;
    }

Next, calling `parseIdFilter()` in the `Property\Int_::parseUrlFilter()`:

    protected function parseIdFilter(Query $query, string $operator,
        array|string $values): void
    {
        if (!is_array($values)) {
            $values = [$values];
        }

        $items = [];

        foreach ($values as $value) {
            foreach (explode(' ', $value) as $option) {
                if ($option === '') {
                    continue;
                }

                if (($option = filter_var($option, FILTER_VALIDATE_INT))
                    === false)
                {
                    continue;
                }

                $items[] = $option;
            }
        }

        if (empty($items)) {
            return;
        }

        switch($operator) {
            case '': $query->whereIn($this->name, $items); break;
            case '-': $query->whereNotIn($this->name, $items); break;
        }
    }

Basically, the code add a filter to the `Ui\Query` using `$query->whereIn($this->name, $items)`. 

## Running Database-Only Query

[I've alreasy mentioned](11-data-current-goal-data-combining-elasticsearch-and-mysql-rendering-faceted-navigation.md#status) that `Ui\Query` has two strategies for executing the query:

1. **Search + Database**. If facets or search are involved, it gets product IDs from the search index, and then retrieves the rest properties from the database.
2. **Database-Only**. Otherwise, it just runs a database query without touching the search index at all.

Currently, the form page doesn't require facets (it will later), and `Ui\Query` resolves to the database-only strategy.

The funny thing - applying filters to the database query is not implemented yet. Let's implement it:

    // Osm\Admin\Ui\Filter\In_
    public function queryDb(DbQuery $query): void {
        if (count($this->items) == 1) {
            $query->where("{$this->property_name} = ?", $this->items[0]);
        }
        else {
            $query->where("{$this->property_name} IN (" .
                implode(', ', $this->items) .
                ")");
        }
    }

## Implementing `?` In Formulas

It's not over - using `?` in formula syntax is not implemented yet. Let's implement it now:

    // Osm\Admin\Queries\Formula\Parameter
    
    public function resolve(Table $table): void
    {
        global $osm_app; /* @var App $osm_app */

        $dataTypes = $osm_app->modules[Module::class]->data_types;

        if (is_bool($this->parameter)) {
            $this->data_type = $dataTypes['bool'];
        }
        elseif (is_int($this->parameter)) {
            $this->data_type = $dataTypes['int'];
        }
        elseif (is_float($this->parameter)) {
            $this->data_type = $dataTypes['float'];
        }
        elseif (is_string($this->parameter)) {
            $this->data_type = $dataTypes['string'];
        }
        else {
            throw new NotSupported(__(
                "Type of ':parameter' is not supported",
                ['parameter' => $this->parameter]));
        }
        $this->array = false;
    }

    public function toSql(array &$bindings, array &$from, string $join): string
    {
        $bindings[] = $this->parameter;

        return '?';
    }

      