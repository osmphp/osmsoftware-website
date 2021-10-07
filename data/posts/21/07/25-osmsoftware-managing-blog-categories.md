# Managing Blog Categories

This article explains how to manage and assign blog categories.

{{ toc }}

### meta.abstract

This article explains how to manage and assign blog categories.

## Defining Categories

Define categories in `data/posts__categories` directory. For each category, create a Markdown file, with file names following `{sort_order}-{url_key}.md` naming convention:

    2-status.md
    3-framework.md
    ...

Each file defines category title and description:

    # Status Reports
    
    Here is what we've been working on lately.

## Adding Metadata

Just like blog posts, category markdown files may have [`meta`](../05/19-osmsoftware-writing-blog-posts.md#meta-section) and [`meta.*`](../05/19-osmsoftware-writing-blog-posts.md#meta-sections) sections. However, categories have different fields than blog posts.

Supported category fields in `meta` section:

* `post_title` - Text to be added to every blog post where this category is assigned as main category. If omitted, category title is used. 

Supported `meta.*` sections:

* `description` section specifies text to be rendered in category page's meta description that is shown on search engine result pages. Don't use Markdown formatting in this section. If omitted, the description text is used. If the description text contains Markdown formatting, do define `meta.description` section without any Markdown formatting in it. 

## Assigning Categories To Blog Posts

### Main Category

Add category URL key to the blog post file name. For example, in order to assign `framework` as main category to a blog post, use `18-framework-introduction.md` instead of `18-introduction.md` file name.

### Additional Categories

Add category URL to the `categories` metadata field of the blog post:

    ### meta

        {
            "categories": ["drafts"]
            ...
        } 
    
## Reindex

After any changes to blog categories, run `osm index`. 

After changing category URL keys and reassigning them to blog posts, run `osm index -f`.  
