# 2021 Sep 27 - Oct 08

**`osm.software` website**. From now on, Osm Framework documentation resides in the [`docs/`](https://github.com/osmphp/framework/tree/HEAD/docs) directory of the `osmphp/framework` repository, and it is displayed in a [separate section of `osm.software` website](https://osm.software/docs/framework/). New data source indexing engine allows running complex interdependent data synchronization with a single command, `osm index`. New `Placeholder` class simplifies dynamic Markdown content generation.

**Osm Framework**. Apply dynamic traits within the same file using `#[UseIn]` attribute. Implement fast dynamic routing using new `DynamicRoute` class. Generate URLs using new `$osm_app->base_url` property.  

More details:

{{ toc }}

### meta.abstract

**`osm.software` website**. From now on, Osm Framework documentation resides in the *`docs/`* directory of the `osmphp/framework` repository, and it is displayed in a *separate section of `osm.software` website*. New data source indexing engine allows running complex interdependent data synchronization with a single command, `osm index`. New `Placeholder` class simplifies dynamic Markdown content generation.

**Osm Framework**. Apply dynamic traits within the same file using `#[UseIn]` attribute. Implement fast dynamic routing using new `DynamicRoute` class. Generate URLs using new `$osm_app->base_url` property.

## *osm.software* Website v0.4.0

[Diff](https://github.com/osmphp/osmsoftware-website/compare/v0.3.0...v0.4.0)

### New Documentation Home

From now on, Osm Framework documentation resides in the [`docs/`](https://github.com/osmphp/framework/tree/HEAD/docs) directory of `osmphp/framework` repository, and it is displayed in a [separate section of `osm.software` website](https://osm.software/docs/framework/):

![`osm.software` documentation section](osm-software-documentation-section.png)

The documentation *books* and *versions* are configured in [`settings.php`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/settings.php):

    ...
    /* @see \Osm\Docs\Docs\Hints\Settings\Docs */
    'docs' => (object)[
        'index_modified' => true,
        'books' => [
            /* @see \Osm\Docs\Docs\Hints\Settings\Book */
            'framework' => (object)[
                'repo' => 'https://github.com/osmphp/framework.git',
                'path' => "{$osm_app->paths->temp}/docs/framework",
                'dir' => 'docs',
                'color' => 'green-700',
                'versions' => [
                    /* @see \Osm\Docs\Docs\Hints\Settings\Version */
                    '0.12' => (object)['branch' => 'v0.12'],
                    '0.13' => (object)['branch' => 'v0.13'],
                ],
            ],
        ],
    ],

The specified versions are cloned/pulled into the specified `temp/` directory, and the documentation index in MySql and ElasticSearch is update from the command line: 

    osm docs:pull
    osm index 

### Future Reusable Packages

We've decided that reusable solutions - flat-file blog, flat-file docs, and the data management logic that they have in common - built for [osm.software](https://osm.software/) website should live as separate Composer packages, so you'll be able to use them in any project. 

Before creating the first versions of `osmphp/blog`, `osmphp/docs` and `osmphp/data`, they should be uncoupled from the website. Until then, we moved all these pieces from `My\` namespace to, `Osm\Blog` and `Osm\Docs` namespaces respectively, replaced all references in the codebase, put the code into the `packages/` directory, and configured `composer.json` to load classes of the future packages:

    {
        ...
        "autoload": {
            "psr-4": {
                "My\\": "src/",
                "Osm\\Framework\\": "packages/framework/",
                "Osm\\Data\\": "packages/data/",
                "Osm\\Blog\\": "packages/blog/",
                "Osm\\Docs\\": "packages/docs/"
            }
        },
        ...
    }

### Renamed Console Commands

Blog-specific console commands had too generic names, they are currently renamed:

    # osm index
    osm index:blog 
    
    # osm check:index
    osm check:blog-index
    
    # osm check:links
    check:blog-links

### New Indexing Engine

The documentation indexing is implemented on top of brand-new data indexing engine. Later, the blog indexing will be rewritten using this engine, too.

Data sources (or just *sources*) store data. For example, the documentation is stored in the file system, in the database, and in a search index. Each of these storages is a source, named `docs`, `db__docs`, and `search__docs`, respectively.

Indexers synchronize data across the sources. An indexer reads the data changed in one or more sources, transforms it and writes to a *target* source. In the documentation subsystem, one indexer updates `db__docs` from `docs`, and another one updates `search__docs` from `db__docs`.

In more complex applications, lots of indexers may sync multiple targets from multiple sources. The indexing engine orchestrates the execution of the indexers, so that dependent indexers are executed after their dependencies. Run all the indexers with a single command:

    # only process invalidated data
    osm index
    
    # re-index everything anew
    osm index -f
    
    # invalidate and process a source
    osm index docs

### One More Example Of Dynamic Routing

Dynamic routing of documentation page is quite sophisticated and, yet, elegant, check [`packages/docs/Docs/Routes/Front/Dynamic.php`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/packages/docs/Docs/Routes/Front/Dynamic.php):

* a book usually starts with a single version; while it's the case, it's accessible at `/docs/framework/...` URL. 
* multi-versioned book redirects `/docs/framework/...` to the latest book version `docs/framework/0.13...`. 
* `docs/framework/0.13` and `docs/framework/0.13/` redirect to `docs/framework/0.13/index.html`
* and more

### Markdown Placeholders 

In addition to the [`{{ toc }}`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/packages/data/Markdown/Placeholder/Toc.php) placeholder, which collects all Markdown file headings and shows them as a table of contents, there is a new [`{{ child_pages }}`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/packages/docs/Docs/Placeholder/ChildPages.php) placeholder in the documentation pages that lists all the child documentation pages and their abstracts.

Define your own placeholders by deriving the [`Placeholder`](https://github.com/osmphp/osmsoftware-website/blob/HEAD/packages/data/Markdown/Placeholder.php) class, specifying its name, what type of Markdown file it's applicable to, and whether it's required to start from a new line:

    <?php
    
    namespace Osm\Docs\Docs\Placeholder;
    
    use Osm\Core\Attributes\Name;
    use Osm\Data\Markdown\Attributes\In_;
    use Osm\Data\Markdown\File;
    use Osm\Data\Markdown\Placeholder;
    
    #[Name('child_pages'), In_(Page::class)]
    class ChildPages extends Placeholder
    {
        public bool $starts_on_new_line = true;
    
        public function render(File $file): ?string
        {
            ...
        }
    }
    
### Other Changes

* Better handling of an empty blog.
* Apply dynamic traits using `#[UseIn]` attribute.

## Osm Core v0.10.0

[Diff](https://github.com/osmphp/core/compare/v0.9.2...v0.10.0)

### New Way Of Applying Dynamic Traits

From now on, `Module::$traits` property is obsolete. Instead, specify the class you are extending directly in the dynamic trait class using [`#[UseIn]`](https://github.com/osmphp/core/blob/HEAD/src/Attributes/UseIn.php) attribute:

    use Osm\Core\Attributes\UseIn;
    ...
    #[UseIn(Foo::class)]
    trait FooTrait
    {
    
    }

### Sorting By Dependency

Application packages, modules, themes and data source indexers are all sorted by dependency using new [`sort_by_dependency()`](https://github.com/osmphp/core/blob/HEAD/runtime/functions.php) helper function. Calling this function may seem a bit clunky, but it does the job:

    return sort_by_dependency($packages, 'Packages',
        fn($positions) =>
            fn(Package $a, Package $b) =>
                $positions[$a->name] <=> $positions[$b->name]
        );

Objects that are being sorted should have unique `name`, and `after`  - am array of object names it should go after:

    /**
     * @property string $name
     * @property string[] $after
     * ...
     */
    class Package extends Object_ {
    }   
 
## Osm Framework v0.13.1

[Diff](https://github.com/osmphp/framework/compare/v0.12.8...v0.13.1)

**Note**. When upgrading to this framework version, apply all your project dynamic traits using the [`#[UseIn]` attribute](#new-way-of-applying-dynamic-traits).

Changes:

* Clear the application cache using new `osm refresh` command.
* New `DynamicRoute` class, based on `nikic/fast-route` package.
* New `AddTrailingSlash` route class, that redirects to the incoming URL with added `/` character in its path.
* New `$osm_app->base_url` property. Set it in tests to null, initialize it in console commands from settings; otherwise, the base URL of the current request is used.
* Dynamic traits are applied using the `#[UseIn]` attribute.
* More directories watched with Gulp.
* Fixed GitHub `test` action.

## Project Template v0.13.0

[Diff](https://github.com/osmphp/project/compare/v0.12.1...v0.13.0)

The only change is using the `^0.13` version of Osm Framework. 

