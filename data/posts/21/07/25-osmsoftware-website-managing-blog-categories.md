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

## Categories

A blog post may be a part of one or more categories. The reader may click on a category, and see all the other posts of that category.

Categories are defined in `data/posts__categories` directory, with file names following `{sort_order}-{url_key}.md` naming convention:

    2-status.md
    3-framework.md
    ...
    
The main category is assigned to a post by adding it to the post file name. For example, `21/05/18-framework-introduction.md` indicates `framework` category, `21/06/25-status-1.md` indicates `status` category, and so on. 

