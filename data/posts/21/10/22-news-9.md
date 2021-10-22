# 2021 Oct 11 - Oct 22

This sprint was dedicated to writing Osm Framework documentation, and indeed, there are 8 new in-depth documentation articles, 4-5 minutes long each. 

Despite documentation focus, I kept improving the website. From now on, you can assign a canonical URL to a blog post or a documentation page, and use GitHub friendly relative URLs in documentation.   

But the most important thing - I started sharing progress and insights daily on [Twitter](https://twitter.com/v_osmianski).

More details:

{{ toc }}

### meta.abstract

This sprint was dedicated to writing Osm Framework documentation, and indeed, there are 8 new in-depth documentation articles, 4-5 minutes long each.

Despite documentation focus, I kept improving the website. From now on, you can assign a canonical URL to a blog post or a documentation page, and use GitHub friendly relative URLs in documentation.

But the most important thing - I started sharing progress and insights daily on *Twitter*.

## Osm Framework v0.13.4

[Diff](https://github.com/osmphp/framework/compare/v0.13.1...v0.13.4)

### New Documentation Pages

* Getting Started
    * [Project Structure](https://osm.software/docs/framework/getting-started/project-structure.html)
    * [Configuration](https://osm.software/docs/framework/getting-started/configuration.html)
    * [Web Server](https://osm.software/docs/framework/getting-started/web-server.html)
* Writing PHP Code
    * [Hint Classes](https://osm.software/docs/framework/writing-php-code/hint-classes.html)
    * [Reflection](https://osm.software/docs/framework/writing-php-code/reflection.html)
    * [Testing](https://osm.software/docs/framework/writing-php-code/testing.html)
* Creating Web Applications
    * [Request-Response Loop](https://osm.software/docs/framework/creating-web-applications/request-response-loop.html)
    * [Themes And Assets](https://osm.software/docs/framework/creating-web-applications/themes-and-assets.html)   

### New Features    

* `std-pages::layout` Blade component accepts and renders `canonicalUrl`.
* Most application settings, if omitted, inherit their values from environment variables.

## *osm.software* Website v0.4.1

[Diff](https://github.com/osmphp/osmsoftware-website/compare/v0.4.0...v0.4.1)

### New Content

* [`NotImplemented`](18-framework-not-implemented.md)
* All the documentation that was originally written as blog posts, and now moved to the documentation section, is properly marked, both visually and using canonical URLs.

### New Features

* Let Google know about the original source of content by assigning `canonical_url` in the `meta` section of any Markdown document
* Use relative links on documentation pages that work both on GitHub, and on [osm.software](https://osm.software/).

## Osm Core v0.10.1

[Diff](https://github.com/osmphp/core/compare/v0.10.0...v0.10.1)

A single fix has been made that allows using `static::new()` in your classes. 

## Project Template v0.13.1

[Diff](https://github.com/osmphp/project/compare/v0.13.0...v0.13.1)

Empty settings files are added. 

