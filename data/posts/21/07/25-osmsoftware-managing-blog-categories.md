# Managing Blog Categories

***It's a draft**. This post is being written. It may contain incomplete information.*

This article explains how to manage and assign blog categories.

{{ toc }}

## meta

    {
        "categories": ["drafts"]
    }

### meta.list_text

This article explains how to manage and assign blog categories.

## Defining Categories

Define categories in `data/posts__categories` directory. For each category, create a Markdown file, with file names following `{sort_order}-{url_key}.md` naming convention:

    2-status.md
    3-framework.md
    ...

Just like blog posts, category markdown files may have [`meta`](../05/19-osmsoftware-writing-blog-posts.md#-meta-section) and [`meta.*`](../05/19-osmsoftware-writing-blog-posts.md#-meta-sections) sections


A blog post may be a part of one or more categories. 
    
The main category is assigned to a post by adding it to the post file name. For example, `21/05/18-framework-introduction.md` indicates `framework` category, `21/06/25-status-1.md` indicates `status` category, and so on. 

