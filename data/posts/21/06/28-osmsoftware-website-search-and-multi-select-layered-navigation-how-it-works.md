# Search And Multi-Select Layered Navigation: How It Works

Readers of `osmcommerce.com` blog can search the blog for a specific phrase, and narrow down listed articles using multi-select layered navigation. Let's see how it works under the hood.

{{ toc }}

### meta.list_text

Readers of `osmcommerce.com` blog can search the blog for a specific phrase, and
narrow down listed articles using multi-select layered navigation. Let's see how
it works under the hood.

## Filters

A **filter** is a part of a page that when applied, narrows the selection of displayed blog posts. 

Most filters are displayed in the left sidebar. Currently, there are category and date filters, but you can add more:

![Sidebar Filters](sidebar-filters.png)

One exception. The search filter is displayed in the page header:

![Search Filter](search-filter.png)

The list of available filters is defined in the [`Posts::$filters`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/src/Posts/Posts.php) property getter:

    protected function get_filters(): array {
        return [
            'q' => Filter\Search::new([
                'name' => 'q',
                'collection' => $this,
            ]),
            'category' => Filter\Category::new([
                'name' => 'category',
                'collection' => $this,
            ]),
            'date' => Filter\Date::new([
                'name' => 'date',
                'collection' => $this,
            ]),
        ];
    }
