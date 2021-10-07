# 2021 Aug 30 - Sep 10

From now on, this website is [deployed on push](07-osmsoftware-deploying-updates.md), and by the way, you can [easily play](../08/30-osmsoftware-installation.md) with the website copy locally. In the Osm framework, new `Osm_Project` application allows reflecting over modules and classes regardless to which application they belong. New [experimental project](https://github.com/osmphp/sample-admin) is aimed at quick creation of the Admin UI.

More details:

{{ toc }}

### meta.abstract

From now on, this website is *deployed on push*, and by the way, you can *easily play* with the website copy locally. In the Osm framework, new `Osm_Project` application allows reflecting over modules and classes regardless to which application they belong. New *experimental project* is aimed at quick creation of the Admin UI.

## Sample Admin Panel Project v0.1.0

[Initial iteration](https://github.com/osmphp/sample-admin/tree/v0.1.0)

An experimental project that will allow quick creation of the Admin UI and the HTTP API.

The whole idea is, that you'll define what kind of data the application operates, and the admin user interface will be generated from that. For example, the following class should create a table in the database, a search index, the product grid and the product editing form in the system backend:

    /**
     * @property string $sku #[
     *      DbColumn\String_, Filterable, Searchable, Sortable,
     *      Field\String_('SKU'), Column
     * ]
     * @property string $description #[
     *      DbColumn\Data, Searchable,
     *      Field\Text('Description'), Column
     * ]
     */
    #[Table('products')]
    class Product extends Record
    {
    }    

In the first iteration, the first class and property attributes (`Table`, `DbColumn`, and more) have been defined.

We have also created the `osmt generate` command that will generate everything. In the initial iteration, it generates migration files that create database tables and search indexes. 

## *osm.software* Website v0.2.3

[Diff](https://github.com/osmphp/osmsoftware-website/compare/v0.2.2...v0.2.3)

### New Content

New articles have been written, and previous articles have been edited and
revised:

* *osm.software* Website
    * [Installation](../08/30-osmsoftware-installation.md) 
* Osm Framework
    * [Logging](08-framework-logging.md) 
* Meta (how we work)
    * [Contributing Changes](02-meta-contributing-changes.md)
    * [Initial Releases](03-meta-initial-releases.md)
* `osm.software` Website
    * [Deploying Updates](07-osmsoftware-deploying-updates.md)

### Other Changes

* *Status reports* category renamed to *News*
* The latest news are shown directly on home page
* All changes are automatically deployed to production on Git push
* New readme

## Osm Framework v0.11.2

[Diff](https://github.com/osmphp/framework/compare/v0.10.2...v0.11.2)

**Important**. After switching to this major version, apply [these changes](https://github.com/osmphp/project/commit/2e4620e6ae41c75f04378cddea724f1ca1661ff7) to the project files.

Changes and fixes:

* `Descendants::byName()` can collect classes not only by `#[Name]` attribute, but also by another compatible attribute
* Console treats PHP warnings and errors as exceptions
* URLs of failed pages are collected in `http.log`

## Project Template v0.11.0

[Diff](https://github.com/osmphp/project/compare/v0.10.0...v0.11.0)

* switched to framework: `^0.11`
* new `Osm_Project` app added to Gulp configuration
* deployment script, and a GitHub action that invokes it added to the project. Read [this article](07-osmsoftware-deploying-updates.md) for a real-world example.

## Osm Core v0.9.1

[Diff](https://github.com/osmphp/core/compare/v0.8.12...v0.9.1)

The new version allows introspecting all modules and classes of
the project regardless to which application they belong. Use it as follows:

    use Osm\Project\App;
    ...
    
    $result = [];
    
    Apps::run(Apps::create(App::class), function (App $app) use (&$result) {
        // use $app->modules and $app->classes to introspect 
        // all modules and classes of the projects and its 
        // installed dependencies
        
        // put the introspection data you need into `$result`. Don't reference
        // objects of the `Osm_Project` application, as they'll won't work 
        // outside this closure. Instead, create plain PHP objects and arrays, 
        // and fill them as needed 
    });

**Notice**. The `Osm_Project` application is for introspection only. Invoking user code in context of this application may produce unexpected results.

