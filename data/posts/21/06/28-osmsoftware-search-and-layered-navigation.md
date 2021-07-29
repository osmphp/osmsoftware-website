# Search And Layered Navigation

***It's a draft**. This post is currently being written.*

Readers of [osm.software](https://osm.software/blog/) blog can search the blog for a specific phrase, and narrow down listed articles using multi-select layered navigation. Let's see how it works under the hood.

{{ toc }}

## meta

    {
        "categories": ["drafts"]
    }

### meta.list_text

Readers of *osm.software* blog can search the blog for a specific phrase, and
narrow down listed articles using multi-select layered navigation. Let's see how
it works under the hood.

## Filters

A **filter** is a part of a page that when applied, narrows the selection of displayed blog posts. 

Most filters are displayed in the left sidebar. Currently, there are category and date filters, but you can add more:

![Sidebar Filters](sidebar-filters.png)

One exception. The search filter is displayed in the page header:

![Search Filter](search-filter.png)

### Defining Filters

Define available filters in the [`Posts::$filters`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/src/Posts/Posts.php) property getter:

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

All three filters extend [`Filter`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/src/Posts/Filter.php) class.

### Rendering Filters

Page Blade templates, for example [`all.blade.php`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/themes/_front__tailwind/views/posts/pages/all.blade.php), render filters in the sidebar using the following code snippet:

    @foreach ($posts->filters as $filter)
        @if ($filter->visible)
            <x-dynamic-component :component="$filter->component"
                :filter="$filter" />
        @endif
    @endforeach
 
`$filter->visible` is always `false` for the search filter, and it's never displayed in the sidebar. For the category and date filters, `visible` is true unless the underlying search query returns no facet counts. 

### Filter Blade Components And Templates

`$filter->component` handles actual rendering. It returns a [Blade component](https://laravel.com/docs/blade#components), for example, [`Filter\Category`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/src/Posts/Filter/Category.php) returns `posts::filter.category`.

The Blade component is a pair of PHP object containing data to be displayed, and the template. For example, `posts::filter.category` component name resolves to the [`Components\Front\Filter\Category`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/src/Posts/Components/Front/Filter/Category.php) component class, and the [`posts::components.filter.category`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/themes/_front__tailwind/views/posts/components/filter/category.blade.php) Blade template.   

The Blade template of a filter renders filter items. For example, the [`posts::components.filter.category`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/themes/_front__tailwind/views/posts/components/filter/category.blade.php) template renders them as follows:

    ...
    <h2 class="text-xl font-bold mt-8 mb-4">{!! $filter->title_html !!}</h2>
    <ul>
        @foreach($filter->items as $item)
            @if ($item->visible)
                <li>
                    ...
                </li>
            @endif
        @endforeach
    </ul>

The result:

![Category Filter](category-filter.png)

### Filter Items

Filter items are PHP objects constructed from the search query result. For example, [`Filter\Category`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/src/Posts/Filter/Category.php) constructs them as follows:

    protected function get_items(): array {
        $items = [];

        foreach ($this->facet->counts as $facetItem) {
            $item = FilterItem\Category::new([
                'filter' => $this,
                'value' => $facetItem->value,
                'count' => $facetItem->count,
            ]);

            if ($item->category) {
                $items[] = $item;
            }
        }

        usort($items, fn(FilterItem\Category $a, FilterItem\Category $b)
            => $a->category->sort_order <=> $b->category->sort_order);
        return $items;
    }

### Rendering Search Form

As already mentioned, the search filter is not rendered in the sidebar using a Blade component. Instead, it is rendered in the page header template, [`posts::components.header`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/themes/_front__tailwind/views/posts/components/header.blade.php):

    ...
    <ul class="flex px-4 mb-4 bg-white">
        ...
        <li class="w-32 h-10 flex-grow flex items-center">
            <form action="{{ "{$osm_app->http->base_url}/blog/search" }}" class="flex-grow">
                <div class="flex border-b py-1 border-solid border-gray-500">
                    <button aria-label="{{ \Osm\__("Search") }}" type="submit"
                        class="w-6 h-6 mr-2 flex items-center justify-center
                            focus:outline-none"
                    >
                        <i class="fas fa-search"></i>
                    </button>
                    <input type="text" name="q"
                        placeholder="{{ \Osm\__("Search blog") }}"
                        class="w-20 flex-grow focus:outline-none"
                        value="{{ $osm_app->http->query['q'] ?? '' }}">
                </div>
            </form>
        </li>
        ...
    </ul>

The result:

![Search Filter](search-filter.png)

## Applied Filters

When a reader marks a filter item as checked in the sidebar, or submits a search phrase into the search form in the page header, she *applies the filter*, and the browser loads a page with that filter applied. All applied filters appear in the page URL.

### URL Structure

All URLs have the same structure. For example, let's examine <https://osm.software/blog/search?q=framework&category=framework+osmsoftware&date=2021-05+2021-07#page2> URL:

* `https://` is the *protocol*
* `osm.software` is the *domain*
* `/blog/search` is the *path* 
* `?q=framework&category=framework+osmsoftware&date=2021-05+2021-07` is the *query*
* `#page2` is the *hash*


### Parsing URL Query Parameters

Most of the time, applied filters appear in the URL query. Continuing with the previous example:

* `q=framework` means that the reader entered "framework" search phrase.
* `category=framework+osmsoftware` means that the reader have chosen "Osm Framework" and "osm.software Website" categories (their URL keys are `framework` and `osmsoftware`, accordingly).
* `date=2021-05+2021-07` means that the reader have picked May and July from the date filter.

Each filter object parses its own part of the URL query. For example, [`Filter\Category`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/src/Posts/Filter/Category.php) splits the URL parameter by the space character (the equivalent of `+` URL character), and for every valid category URL key creates an applied filter object:

    protected function get_applied_filters(): array {
        ...
        $appliedFilters = [];

        if (!$this->unparsed_value) {
            return $appliedFilters;
        }

        foreach (explode(' ', $this->unparsed_value) as $urlKey) {
            if (isset($this->category_module->categories[$urlKey])) {
                $appliedFilters[$urlKey] = AppliedFilter\Category::new([
                    'category' => $this->category_module->categories[$urlKey],
                    'filter' => $this,
                ]);
            }
        }

        return array_values($appliedFilters);
    }

### Getting All Applied Filters

The `applied_filters` property of the [`Posts`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/src/Posts/Posts.php) collection object merges all applied filters into a single array: 

    protected function get_applied_filters(): array {
        $appliedFilters = [];

        foreach ($this->filters as $filter) {
            $appliedFilters = array_merge($appliedFilters,
                $filter->applied_filters);
        }

        return $appliedFilters;
    }

### Rendering Applied Filters

Page Blade templates, for example [`all.blade.php`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/themes/_front__tailwind/views/posts/pages/all.blade.php), render applied filters in the sidebar using the following code snippet:

    <x-posts::applied_filters :posts="$posts"/>

The underlying Blade component, [`Components\Front\AppliedFilters`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/src/Posts/Components/Front/AppliedFilters.php), and its template, [`posts::components.applied-filters`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/themes/_front__tailwind/views/posts/components/applied-filters.blade.php), render all applied filters as follows:

    ...
    @if (count($posts->applied_filters))
        <h2 class="text-xl font-bold mt-8 mb-4">
            {{ \Osm\__("Applied Filters") }}
        </h2>
        <ul class="flex flex-wrap">
            @foreach ($posts->applied_filters as $appliedFilter)
                <li class="mr-4">
                    ...
                </li>
            @endforeach
        </ul>
        <p class="mt-4">
            <a href="{{ $posts->url()->removeAllFilters() }}"
                title="{{ \Osm\__("Clear all") }}" class="link"
            >
                {{ \Osm\__("Clear all") }}</a>
        </p>
    @endif

The result:

![Applied Filters](applied-filters.png)

### Putting Applied Filters Into URL Path

For SEO purposes, certain applied filters are putting into the URL path instead of the URL query:

* if there is only one applied category filter, then the generated URL is <https://osm.software/blog/framework/?q=framework&date=2021-05+2021-07> rather than <https://osm.software/blog/search?q=framework&date=2021-05+2021-07&category=framework>;

* otherwise, if there is only one applied date filter, then the generated URL is <https://osm.software/blog/2021/05/?q=framework&category=framework+osmsoftware> rather than <https://osm.software/blog/search?q=framework&category=framework+osmsoftware&date=2021-05>

It's good for 3 reasons:

1. Category URL key appears closer to the beginning of the URL, and, hence, ranks higher.

2. Every category, year and month get a dedicated page template, and the specific content tailored at that specific category or date period. For example, category pages display category descriptions.

3. From the outside, the blog seems to have clear hierarchical directory structure:

        https://osm.software/blog/
            framework/
                ...
            osmsoftware/
                ...
            2021/
                05/
                    ...
                06/
                    ...
                07/
                    ...        

### Dedicated Routes

All blog routes, including those for category, year and month pages are handled by the [`Dynamic`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/src/Posts/Routes/Front/Dynamic.php) route. 

Under the hood, it uses [`nikic/fast-route`](https://github.com/nikic/FastRoute) package to recognize the following routes:

    protected function collectRoutes(RouteCollector $r): void {
        $r->get('', AddTrailingSlash::class);
        $r->get('/{year:\d+}', AddTrailingSlash::class);
        $r->get('/{year:\d+}/{month:\d+}', AddTrailingSlash::class);
        $r->get('/{category:(?!search)\w[^/]*}', AddTrailingSlash::class);

        $r->get('/', RenderAllPosts::class);
        $r->get('/search', RenderSearchResults::class);
        $r->get('/{year:\d+}/{month:\d+}/{url_key}.html',
            RenderPost::class);
        $r->get('/{year:\d+}/', RenderYearPosts::class);
        $r->get('/{year:\d+}/{month:\d+}/', RenderMonthPosts::class);
        $r->get('/{category:\w[^/]*}/', RenderCategoryPosts::class);
        $r->get('/{image_path:.*\.(?:jpg|gif|png)}', RenderImage::class);
    }

If the incoming route matches the regular expression, then specified route class is called to handle the request. For example, `/framework` URL matches `/{category:\w[^/]*}/` regular expression, and `RenderCategoryPosts` is called to render the category page. In addition, fetched `category` regular expression group is passed to the `RenderCategoryPosts` route constructor.

The route does two things. It creates a `PageType` object, so that the filtering engine can apply a filter from the URL path, and renders a dedicated template:

    public function run(): Response {
        $pageType = PageType\Category::new([
            'category_url_key' => $this->category,
        ]);

        if (!$pageType->category) {
            throw new NotFound();
        }

        return view_response('posts::pages.category', [
            'posts' => Posts::new(['page_type' => $pageType]),
        ]);
    }

### Page Types

    //TODO

### Dedicated Page Templates

## Generating Filtered URLs

### Using `URL` Class

### Concatenating URL String

### Submitting Search Form

## Retrieving Blog Post Data

### Searching And Applying Filters

### Retrieving Blog Posts

### Retrieving Not Applied Filter Counts

### Retrieving Applied Filter Counts        

