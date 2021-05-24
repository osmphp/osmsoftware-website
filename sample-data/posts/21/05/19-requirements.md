# Requirements

As [mentioned before](18-welcome.md), this very website is based on the `osmphp/framework` package. This blog post is the first in the series describing how exactly it was built. Before diving into implementation details, let's write the requirements of how it is expected to work. 

{{ toc }}

## Header

## Header 

## meta

    {
        "series": {"Building osmcommerce.com": {"part": 1} }
    }

### meta.summary

Before diving into implementation details, let's write the requirements of how
`osmcommerce.com` website is expected to work.

## Editing workflow

There are actually two projects:

* `osmcommerce.local` works on my local machine
* `osmcommerce.com` is publicly available

Like [Jekyll](https://jekyllrb.com/), the project contains both code (PHP, JS and other files) and data (blog posts written in Markdown). Unlike Jekyll, it also uses the MySql database for storing comments, and the ElasticSearch for filtering and search.

The editing workflow:

1. I edit Markdown files locally, then run `osm index:blog` command to update the ElasticSearch index, then check the resulting `osmcommerce.local` website in the browser.
2. I push the project repository to GitHub, it sends push notification to `osmcommerce.com`.
3. `osmcommerce.com` updates itself from the GitHub repository, and runs `osm index:blog` command to update its ElasticSearch index, too.

## Blog post directory

The blog posts are (almost) regular Markdown files located in the `data/posts` directory of the project:

    data/
        posts/
            21/
                05/
                    18-introducing-osm-commerce.md
                    20-requirements-for-osmcommerce.com.md
                    ...

As you can see, the post creation date as well as post URL key are encoded in the directory structure, and the file name:

    data/posts/{yy}/{mm}/{dd}-{url_key}.md

The full blog post URL reflects the directory structure, with the day omitted, and `.md` extension replaced with `.html`:

    https://www.osmcommerce.com/blog/21/05/introducing-osm-commerce.html
    https://www.osmcommerce.com/blog/21/05/initial-requirements-for-osmcommerce.com.html
    ...

## Placeholders

A blog post may use placeholders that expand dynamically when the page is rendered. Currently, there is only one placeholder:

* `{{ toc }}` - collects headings into the table of contents.

## Variables

A blog post may contain variables - placeholders that a reader may replace with their own values. For instance, a blog post may provide update instructions like:

    cd {{ project_dir }}
    composer update

The reader may assign `{{ project_dir }}` variable some specific value and get the instruction just for her project environment:

    cd /home/vagrant/osmcommerce
    composer update

Every blog post defines variables in its metadata.

## Tags

A blog post may be tagged in its metadata. The reader may click on a tag and see all the other posts containing this tag.

## Metadata

Finally, a blog post may contain metadata - JSON with additional information about the post. The metadata section is marked with the `.meta` CSS class:

    ## Metadata {.meta}
    
        {
            "tags": {
                "creating-osmcommerce.com": "Creating osmcommerce.com"
            },
            "variables": {
                "project_dir": "Project directory, e.g. '/home/vagrant/osmcommerce'",
                "module_namespace": "Module namespace, e.g. 'My\\Module'"
            }
        }

The metadata section is not rendered. Instead, the metadata is used for blog navigation, variable substitution, SEO, and other purposes.

## Breadcrumbs

Breadcrumbs for a blog post reflect the directory structure:

    Blog -> 2021 -> 05

The reader may click on a breadcrumb and see all the other posts matching specified date. 

## Search

The reader may use the search input. The matching blog posts are shown on the `/search` page.

## Infinite scrolling

Instead of traditional pagination, tag, blog/year/month and search pages employ infinite scrolling.

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

## Broken links

`osm show:broken-links` command will scan all the Markdown files, check all the relative links inside them, and report the broken ones. 

## Static pages

Unlike blog posts, home page data is hard-wired in code. The same is true for other static pages (e.g. privacy policy).

## Redirects

Eventually, some posts will be renamed, some other posts will be moved to the documentation section. Redirects can be created for such cases, either by adding `redirect_to` setting to the metadata, or by creating `data/posts/{yy}/{mm}/{dd}-{url_key}.json` file:

    {
        "redirect_to": "new-url-key.html"
    }

## Comments

The comments (and users) are stored in the MySql database:

    posts
        id              int auto_increment
        url_key         varchar
        redirect_to_id  int nullable -> posts.id on delete set null
    posts__comments
        id              int auto_increment
        user_id         int nullable -> users.id on delete set null
        post_id         int -> posts.id on delete cascade
        created_at      datetime default(NOW())
        text            longtext
    users
        id              int auto_increment
        created_at      datetime
        active          bool
        username        varchar
        email           varchar
        password_hash   varchar
        
For comments to work, typical `/login`, `/register`, `/logout`, `/confirm-email`, `/forgot-password`, `/reset-password` and `/account` routes and pages have to be implemented. 

