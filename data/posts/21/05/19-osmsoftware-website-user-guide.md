# User Guide

***It's a draft**. This post had been written before actually implementing `osm.software` website, as if it's already implemented. Hence, it may substantially differ from actual implementation.*

This very website, `osm.software`, is built using Osm Framework.
It's [open-source](https://github.com/osmphp/osmsoftware-website), but before
diving into implementation details, let's review its initial requirements.

{{ toc }}

## meta

    {
        "categories": ["drafts"]
    }

### meta.list_text

This very website, `osm.software`, is built using Osm Framework. It's open-source, but before diving into implementation details, let's review its initial requirements.

## Introduction

There are actually two projects:

* `osmsoftware.local` works on my local machine
* `osm.software` is publicly available

Like [Jekyll](https://jekyllrb.com/), the project contains both code (PHP, JS and other files) and data (blog posts written in Markdown). Unlike Jekyll, it also uses the MySql database for storing comments, and the ElasticSearch for maintaining its filtering and search index.

## Editing Workflow

1. I edit Markdown files locally, then run `osm index` command to update the MySql database table, and the ElasticSearch index, then check the resulting `osmsoftware.local` website in the browser.
2. I push the project repository to GitHub, it sends push notification to `osm.software`.
3. `osm.software` updates itself from the GitHub repository, and runs `osm index` command to update its MySql database table and ElasticSearch index, too.

## Blog Post Directory

The blog posts are (almost) regular Markdown files located in the `data/posts` directory of the project:

    data/
        posts/
            21/
                05/
                    18-framework-introduction.md
                    19-osmsoftware-website-requirements.md
                    ...

As you can see, the post creation date as well as post URL key are encoded in the directory structure, and the file name:

    data/posts/{yy}/{mm}/{dd}-{url_key}.md

The full blog post URL reflects the directory structure, with the day omitted, and `.md` extension replaced with `.html`:

    https://osm.software/blog/21/05/framework-introduction.html
    https://osm.software/blog/21/05/osmsoftware-website-requirements.html
    ...

## Placeholders

A blog post may use placeholders, starting with `{{` and ending with `}}`, that expand dynamically when the page is rendered. Currently, there is only one placeholder:

* `toc` - collects headings into the table of contents.

## Categories

A blog post may be a part of one or more categories. The reader may click on a category, and see all the other posts of that category.

Categories are defined in `data/posts__categories` directory, with file names following `{sort_order}-{url_key}.md` naming convention:

    2-status.md
    3-framework.md
    ...
    
The main category is assigned to a post by adding it to the post file name. For example, `21/05/18-framework-introduction.md` indicates `framework` category, `21/06/25-status-1.md` indicates `status` category, and so on. 

## Metadata

Finally, a blog post may contain metadata - JSON with additional information about the post. The metadata section is marked with `meta` title:

    ### meta

        {
            "canonical_url": "...",
            ...
        }

Alternatively, you can provide additional meta information in Markdown format in `meta.*` sections. For example, `list_text` field specifies text to be rendered on blog post list pages. 

    ### meta.list_text
    
    This very website, `osm.software`, is built using Osm Framework. It's open-source, but before diving into implementation details, let's review its initial requirements.

The metadata section is not rendered as is, but it's used for navigation, SEO, and other purposes.

## Search

The reader may use the search input. The matching blog posts are shown on the `/search` page.

## Layered Navigation

The reader may pick one or more categories, one or more calendar periods, and the matching blog posts should be shown.

## Links

Blog posts may contain relative links to other blog posts. The general rule is that links should work even if you click on them in the GitHub repository. It means that they should contain the exact filename to a referenced Markdown file:

    # link with a title
    see [Getting Started](../04/08-getting-started.md)

    # link without a title
    <../04/08-getting-started.md>

Non blog post links will be absolute:

    # link to an external website
    <https://www.php.net/>

    # internal links use base_url placeholder
    <{{ base_url }}/privacy-policy.html>

## Images

Blog posts may contain relative links to images. By convention, images are stored in the same directory:

    # show an image from the current directory
    ![Welcome Screen](welcome-screen.png)

## Reporting Broken links

`osm check:links` command will scan all the Markdown files, check all the relative and absolute links inside them, and report the broken ones. 

## Static pages

Unlike blog posts, home page and other static page data is hard-wired in code. 

 