# 2021 Jul 12 - Jul 23

Osm Framework introduced production mode and maintenance mode. [osm.software](https://osm.software/) went live.

More details:

{{ toc }}

### meta.list_text

Osm Framework introduced production mode and maintenance mode. *osm.software* website went live.

## *osm.software* Website v0.2.0

[Diff](https://github.com/osmphp/osmsoftware-website/compare/v0.1.2...v0.2.0)

### Site went live

From now on, <https://osm.software/> is live.

### Minor Changes

* on mobile, the left sidebar opens/closes by clicking the hamburger button
* posts compute and show estimated reading time
* Gulp script adapted for [production mode](#production-mode) 
* main category is a part of post title 

### Fixes

* hide hamburger button if there is no left sidebar    

### Content changes

* rewritten home page contents
* incomplete/incorrect posts marked as drafts
* post-introduction to Osm Framework

## Osm Framework v0.9.3

[Diff](https://github.com/osmphp/framework/compare/v0.9.0...v0.9.3)

### Production Mode

In production, set environment variable `PRODUCTION=true` in order to:

* purge unused CSS
* minify JS and CSS
* serve JS and CSS without source maps
* hide stack traces and move them into `temp/Osm_App/logs/http.log`

### Maintenance Mode

New commands:

    # put the site on maintenance
    osm http:down
    
    # put the site back online
    osm http:up
    
When the site is on maintenance, the site returns 503 page.
    
Test