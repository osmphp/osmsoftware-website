# Initial Releases

This article describes our package release process before `v1.0.0`. In short, every change is released as soon as it's ready, and all dependent packages are updated at the same time.    

Contents:

{{ toc }}

## meta.list_text

This article describes our package release process before `v1.0.0`. In short,
every change is released as soon as it's ready, and all dependent packages are
updated at the same time.    

## Version Tags And Branches

Use [semantic versioning](https://semver.org/).

Mark every new version with `vX.Y.Z` Git tag. Initial version is `v0.1.0`. Major updates increment Y (`v0.2.0`, `v0.3.0`, ...), while minor updates and fixes increment Z (`v0.1.1`, `v0.1.2`, ...).   

Develop `v0.Y.Z` versions on a `v0.Y` long-living Git branch. Initial branch is `v0.1`. 

## Composer Version Constraints

In `composer.json`, use `^0.Y` [version constraint](https://getcomposer.org/doc/articles/versions.md) for the `osmphp/*` dependencies:

    {
        ...
        "require": {
            ...
            "osmphp/framework": "^0.10"
        },
        ...
    } 

This way, minor non-breaking changes and bug fixes, tagged with `v0.10.Z`, will be installed automatically. In order to get major breaking changes, tagged with `v0.11.0`, switch to `^0.11` version constraint, and fix compatibility issues.   

## Release Often

Once a new package feature is ready, even if it's a major one and breaks something, ship it. 

Keep dependent projects and packages up-to-date. Switch to the latest `^0.Y` version constraint, and fix compatibility issues.  

## Support

Fix issues in the latest `v0.Y` branch. Prior versions are not supported. 