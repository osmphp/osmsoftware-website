# 2021 Jun 14 - Jun 25

[osmcommerce.com](https://osm.software/) (now [osm.software](https://osm.software/)) blog got multi-select layered navigation, category management, FontAwesome icons,
Tailwind CSS Typography. Osm Core allows debugging accidental assignments of the computed properties.  
 
{{ toc }}

## meta

    {
        "candidate_posts": [
            "osmsoftware-generating-blog-urls",
            "osmsoftware-dispatching-blog-routes",
            "osmsoftware-parsing-markdown",
            "framework-running-native-php-server",
            "framework-logging",
            "framework-debugging-computed-properties",
            "productivity-keeping-things-easy-to-test",
            "productivity-maximizing-your-output"
        ]
    }

### meta.list_text

*osmcommerce.com* (now *osm.software*) blog got multi-select layered navigation,
category management, FontAwesome icons, Tailwind CSS Typography. Osm Core allows
debugging accidental assignments of the computed properties.

## *osmcommerce.com* Website v0.1.1

[Diff](https://github.com/osmphp/osmcommerce-website/compare/v0.1.0...v0.1.1)

### Reader-Oriented Changes

* show/hide sidebar, search, and access your account using page header
* navigate the blog using multi-select layered navigation, category and date filters
* search the blog, and narrow results using multi-select layered navigation

### Author-Oriented Changes

* configure categories in `data/posts__categories` directory
* assign categories to posts in the file name, or use `categories` meta field 

### Internal Changes

* parse Markdown file metadata using `My\Markdown\File` (or derived) class

* use FontAwesome icons from `My\FontAwesome` module

* use category data:

    * customize category meta data handling in `My\Categories\Category` class
    * access all defined categories using `My\Categories\Module::$categories` 
    property

* style Markdown HTML using Tailwind CSS Typography plugin configured in `themes/_front__tailwind/tailwind.config.js`

* customize layered navigation:

    * create custom `My\Posts\PageType` classes, currently there are `Home`, 
    `Category`, `Search`, `Year` and `Month` page types
    * create custom `My\Posts\Filter` classes, currently there are `Category`, 
    `Date` and `Search` filters
    * render applied filters using custom `My\Posts\AppliedFilter` classes,
      currently there are `Category`, `Date` and `Search` applied filters
    * render multi-select filter items using custom `My\Posts\FilterItem` 
    classes, currently there are `Category`, `Year` and `Month` filter items
    * generate layered navigation URLs using `My\Posts\Posts::url()` method and
    the fluent methods of the `My\Posts\Url` class

* customize new Blade components and their templates:

        # page header
        My\Posts\Components\Front\Header
        themes/_front__tailwind/views/posts/components/header.blade.php
        
        # blog list item
        My\Posts\Components\Front\ListItem
        themes/_front__tailwind/views/posts/components/list-item.blade.php
        
        # category filter
        My\Posts\Components\Front\Filter\Category
        themes/_front__tailwind/views/posts/components/filter/category.blade.php
        
        # date filter
        My\Posts\Components\Front\Filter\Date
        themes/_front__tailwind/views/posts/components/filter/date.blade.php
  
        # applied filters
        My\Posts\Components\Front\AppliedFilters
        themes/_front__tailwind/views/posts/components/applied-filters.blade.php

* customize new page routes and Blade their templates:

        # home page
        My\Posts\Routes\Front\RenderAllPosts
        themes/_front__tailwind/views/posts/pages/all.blade.php
                      
        # category page
        My\Posts\Routes\Front\RenderCategoryPosts
        themes/_front__tailwind/views/posts/pages/category.blade.php
        
        # search page
        My\Posts\Routes\Front\RenderSearchResults
        themes/_front__tailwind/views/posts/pages/search.blade.php
        
        # year page
        My\Posts\Routes\Front\RenderYearPosts
        themes/_front__tailwind/views/posts/pages/year.blade.php
        
        # month page
        My\Posts\Routes\Front\RenderMonthPosts
        themes/_front__tailwind/views/posts/pages/category.blade.php
        
* host the website under the native PHP Web server using 
    `public/Osm_App/router.php` file

* don't assign tags and series to posts anymore - they are no longer supported

## Osm Framework v0.8.4

[Diff](https://github.com/osmphp/framework/compare/v0.8.0...v0.8.4)

* encode/decode parts of the URL using new `url_encode` and `url_decode` functions

## Osm Core v0.8.11

[Diff](https://github.com/osmphp/core/compare/v0.8.10...v0.8.11)

* debug computed properties using new `DebuggableProperties` trait