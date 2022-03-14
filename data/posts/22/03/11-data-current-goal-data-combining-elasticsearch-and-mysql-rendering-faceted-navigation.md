# Current Goal. Combining ElasticSearch And MySql. Rendering Faceted Navigation

My current goal is a minimum list page, a form page and a faceted navigation for a `Product` data class.

Today, I've:

* implemented ElasticSearch + MySql combo that gets the list page data
* started rendering the faceted navigation

More details:
 
{{ toc }}

P.S. [Feel free to DM me](https://twitter.com/v_osmianski) if anything is unclear, or you want to discuss it, or help.

### meta.abstract

My current goal is a minimum list page, a form page and a faceted navigation for a `Product` data class.

Today, I've:

* implemented ElasticSearch + MySql combo that gets the list page data
* started rendering the faceted navigation

## Goal

My current goal is to create some minimum admin UI that actually works. As an example,

I've picked the `Product` class having standard `id` and `title` properties, and one custom `color` property that can be assigned one of predefined color values.

### List Page

Product list page will show up as a multiselect grid with `title` and `color` columns. Clicking on a grid row will open an editing page for the clicked product.

The faceted navigation in the sidebar will contain a single `color` filter show counts for every option based on currently shown product set. You'll be able to filter by one or more `color` values, and see matching product list, and a list of applied filters.

Buttons:

* `Create` button will show a new product page.
* `Edit` button will show an editing page for one or more selected products.
* `Delete` button will delete one or more selected products.

### Form Pages

The new product page, and the product editing page will show the same form with `title` input, and `color` dropdown.

The same faceted navigation will be shown in the sidebar.

Buttons:

* `Close` button will return you to the list page.
* `Save` button will save new or edited product(s).
* `Delete` button will delete edited product(s).

## Status

I've already had the list page showing the products, and now I'm working on the faceted navigation in the sidebar.

For it to work, I created the underlying index in the ElasticSearch. Currently, the only way to update it is to fully rebuild it using `osm index` command.

Well, it will be `osm index` in the product that use Osm Admin. While developing Osm Admin the exact command is `php bin/run.php index`.

Now it's time to implement querying the `color` facet, and display it in the sidebar.

Currently, the list page shows `'Osm\Admin\Ui\Query::runSearchAndDb()' not implemented` exception. This is where I am.

`Ui\Query` is a class responsible for retrieving the data both for the grid (`Ui\List_\Grid` class), and for the faceted navigation in the sidebar (`Ui\Facets` class). The rendering is done in three steps:

1. Both grid and faceted navigation objects tell the UI query object what columns and facets should be shown, and what filters should be applied.
2. The UI query object retrieves the data from the database and the search engine.
3. Grid and faceted navigation objects render the data to HTML using Blade templates.

If no facets are being rendered, the UI query only queries the database. This scenario (without applying filters) is already implemented.

Otherwise:

1. It retrieves matching product IDs and facet counts.
2. It uses these product IDs to retrieve product properties from the  database.

The search+database scenario is yet to be implemented.

## Implementing Search + DB Queries

OK, let's implement it:

    // Ui\Query

    protected function run(): void
    {
        if ($this->executed) {
            return;
        }

        if (!empty($this->query_facets)) {
            $this->runSearchAndDb();
        }
        else {
            $this->runDb();
        }

        $this->executed = true;
    }

    protected function runSearchAndDb(): void {
        $searchQuery = $this->searchQuery();

        foreach (array_unique($this->query_facets) as $propertyName) {
            $searchQuery->facetBy($propertyName);
        }

        $searchResult = $searchQuery->get();

        $this->count = $searchResult->count;
        $this->facets = $searchResult->facets;

        $this->items = count($searchResult->ids)
            ? $this->dbQuery()
                ->where("id IN (" .
                    implode(', ', $searchResult->ids). ")")
                ->get(...array_unique($this->query_selects))
            : [];
    }

    protected function runDb(): void {
        if ($this->query_count) {
            $this->count = $this->dbQuery()->value("COUNT() AS count");
        }

        if ($this->query_items) {
            $this->items = $this->dbQuery()
                ->get(...array_unique($this->query_selects));
        }
    }

This implementation lacks several things:

* it doesn't apply any filters, orders, limit or offset
* search index always return count and items even if they are not needed
* consistency. Search query returns a separate object with the results while UI query assigns results to the same query object.

I'll address these issues later. Now, I have a more urgent one.

## Parsing Formulas

Previously, the `Grid` requested data for its columns like this:

    $this->query->db_query->select(...$this->selects);

Now, it's changed to

    $this->query->select(...$this->selects);

New implementation stores select formulas as text in the `Ui\Query`  object, and passes them to the underlying DB query when query is actually executed.

However, the `Grid` object needs these formulas parsed, as it needs to know the schema information about the selected property, and decide how to render each column.

On the other hand, I still want to avoid parsing the same formulas two times instead of one.

The solution is having an internal `Query` object using it to parse formulas passed to `Ui\Query::select()`.

And yes, it's time to separate query data and duery result data into two separate objects.

## Facet Visibility

Query returns facet counts. The next step is to render them on the list page.

Facets are rendered in the sidebar. If there are no facets, the sidebar is not shown (`ui::layout` Blade template):

    @if(!empty($sidebar) && $sidebar->visible)
        ... render sidebar
    @else
        ... don't render sidebar
    @endif

`Sidebar` is visible if at least one of its items is visible. Currently, it can only show facets. Later, there may be more sidebar items:

    protected function get_visible(): bool {
        return $this->facets?->visible ?: false;
    }

`Facets` navigation is visible if at least one of the facets is visible:

    protected function get_visible(): bool {
        foreach ($this->facets as $facet) {
            if ($facet->visible) {
                return true;
            }
        }

        return false;
    }

Finally, the `color` facet (`Facet\Checkboxes`) class is visible if the facet counts that it had requested earlier are not empty:

    protected function get_visible(): bool {
        return !empty($this->query->result->facets[$this->property->name]);
    }

## "Drawing" `color` Facet Template

Finally, a facet view is rendered in the sidebar:

    ui::sidebar (Sidebar class)
        ui::facets (Facets class)
            ui::facet.checkboxes (Facet\Checkboxes class)

Let's "draw" the `ui::facet.checkboxes` template using Tailwind CSS:

<?php
/* @var string $title */
/* @var \Osm\Admin\Ui\Facet\Option[] $options */
?>
<h2 class="text-xl mt-8 mb-4">{{ $title }}</h2>
<ul>
    @foreach ($options as $option)
        <li class="p-2 my-2 -mx-2">
            <a class="block pl-6 relative" href="{{ $option->url }}"
                title="{{ $option->title }} ({{ $option->count }})"
            >
                <span class="absolute left-0">
                    <input type="checkbox"
                           class="w-4 h-4 bg-gray-50 rounded border
                            border-gray-300 focus:ring-3
                            focus:ring-blue-300 dark:bg-gray-700
                            dark:border-gray-600 dark:focus:ring-blue-600
                            dark:ring-offset-gray-800 cursor-pointer">
                </span>
                <span>{{ $option->title }} ({{ $option->count }})</span>
            </a>
        </li>
    @endforeach
</ul>

It's only the first approximation, I'll tune it up later, when it starts showing some data.

## `color` Facet Template Variables

To make it work, let's pass `title` and `options` variables. The underlying class, `Facet\Checkboxes`, does that in its `get_data()` method.

Here is the first naive version:

    protected function get_data(): array {
        return [
            'title' => $this->property->control->title,
            'options' => $this->query->result
                ->facets[$this->property->name]->counts,
        ];
    }

To be continued ...