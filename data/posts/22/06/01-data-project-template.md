# Project Template

Yesterday, I created a template for new projects powered by Osm Admin.

This way, you can create and publish a project in minutes, just [follow the `README`](https://github.com/osmphp/admin). Yay!

Here is how it went:

{{ toc }}

### meta.abstract

Yesterday, I created a template for new projects powered by Osm Admin.

This way, you can create and publish a project in minutes, just *follow the `README`*. Yay!

## Creating Project Template

The `README` mentions a non-existing `osmphp/admin-project` package. 

Let's create it.

1. The project template is almost identical to the generic project template, `osmphp/project`. I copied its files, and changed the `composer.json` file as follows:

        {
            "name": "osmphp/admin-project",
            "description": "A template for projects powered by Osm Admin",
            ...
            "require": {
                "php": ">=8.1",
                "osmphp/admin": "^0.2"
            },
            ...
        }

2. I created the `osmphp/admin-project` repository, and ran these commands:

        git init
        git add .
        git commit -am "Initial commit"
        git branch -M v0.2
        git remote add origin git@github.com:osmphp/admin-project.git
        git push -u origin v0.2
        
3. I tagged the initial commit as `v0.2.0`. 
4. Finally, I submitted the package to [Packagist](https://packagist.org/).

## Testing Installation

Several things are not going to work, but let's try it anyway, and fix the issues, one by one.

DONE.

By the way, here is a productive way of editing a project template. You can fully install it as a project, use it to edit some MySql database, and this way, find out what works and what doesn't.

However, before committing changes, delete the `composer.lock` file. Never-ever commit this file, and you'll be fine.

  