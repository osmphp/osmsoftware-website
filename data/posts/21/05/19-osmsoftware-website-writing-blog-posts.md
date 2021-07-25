# Writing Blog Posts

***It's a draft**. This post had been written before actually implementing `osm.software` website. Hence, it may substantially differ from actual implementation.*

This article explains how to write and publish blog posts.

We assume that you already have the project up and running. We'll provide the exact installation steps later, in a separate article.

{{ toc }}

## meta

    {
        "categories": ["drafts"],
        "candidate_posts": [
            "osmsoftware-website-installation",
            "osmsoftware-website-managing-blog-categories"
        ]
    }

### meta.list_text

This article explains how to write and publish blog posts.

## Introduction

Like in [Jekyll](https://jekyllrb.com/), there is no user interface. Instead, you edit both content (blog posts, and category definitions written in Markdown), and code (PHP, JS and other files) directly in the filesystem. 

Unlike Jekyll, there is no page generation step - all the content is rendered dynamically, directly from the filesystem. However, the application uses the MySql database for storing comments, and the ElasticSearch for maintaining its filtering and search index, and you have to update them after editing files (it's called "indexing").

## Editing Workflow

### Our Workflow

Run the website both locally (<http://192.168.10.12:8004/>), and on a public server (<https://osm.software/>). 

Edit in two phases:

1. Edit locally.

    1. Create, modify and delete files in the `data/` directory (the directory structure and file formatting are explained in detail further in this document).
     
    2. Run `osm index` command in the project directory.
    
    3. Check how the website looks like and edit the files again if necessary.
     
2. Once you are satisfied with the result, publish the changes to the server.

    1. Commit the changes to the local Git repository, and push them to the Git repository on GitHub. if your editor doesn't provide user interface for that, run the following commands in the project directory:
    
            git commit -am "Writing blog"
            git push
        
    2. Tell the server to download the changes. Consider creating a batch file that do that in one run:

            deploy-osmsoftware

        Internally, it runs the Bash script on the server:

            plink -batch -load "_perkunas (osmsoftware)" "cd ~/www && bash bin/deploy.sh"

        Under the hood, `bin/deploy.sh` does the following:

            git pull 
            osm index

That's how we work, due to the obvious advantages:

1. Git keeps the change history.
2. Editing locally prevents readers to see half-written pieces.  

However, if our workflow seems too complicated, consider using simplified workflow described below instead. 

### Simplified Workflow

Alternatively, install the website on the server only, and edit files directly on the server:

1. Create, modify and delete files in the `data/` directory (the directory structure and file formatting are explained in detail further in this document).
 
2. Run `osm index` command in the project directory.
  
## Blog Post Directory Structure

The blog posts are regular Markdown files located in the `data/posts` directory of the project:

    data/
        posts/
            21/
                05/
                    18-framework-introduction.md
                    19-osmsoftware-website-requirements.md
                    ...

As you can see, the post creation date as well as post URL key are encoded in the file name:

    data/posts/{yy}/{mm}/{dd}-{url_key}.md

The file name is also reflected in the blog post URL. Note that the day part is omitted, and `.md` extension replaced with `.html`:

    https://osm.software/blog/21/05/framework-introduction.html
    https://osm.software/blog/21/05/osmsoftware-website-requirements.html
    ...

If you start the `{url_key}` part with a valid category URL key, then it will be assigned to the blog post as "main category". We'll describe managing blog categories in a separate blog post.

## Placeholders

A blog post may contain placeholders, starting with `{{` and ending with `}}`, that expand dynamically when the page is rendered. Currently, there is only one placeholder:

* `toc` - collects headings into the table of contents.

---

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

 