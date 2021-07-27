# Writing Blog Posts

This article explains how to write and publish blog posts.

Contents:

{{ toc }}

## meta

    {
        "candidate_posts": [
            "osmsoftware-website-installation"
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
  
## Directory Structure

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

    .../blog/21/05/framework-introduction.html
    .../blog/21/05/osmsoftware-website-requirements.html
    ...

If you start the `{url_key}` part with a valid category URL key, then it will be assigned to the blog post as "main category". See [Managing Blog Categories](../07/25-osmsoftware-managing-blog-categories.md) for more details.

## File Format

Write blog posts using [Markdown](https://daringfireball.net/projects/markdown/syntax) and [Markdown Extra](https://michelf.ca/projects/php-markdown/extra/) syntax.

In addition, use placeholders and provide metadata as shown below.

### Placeholders

A blog post may contain placeholders, starting with `{{` and ending with `}}`, that expand dynamically when the page is rendered. 

Currently, there is only one placeholder:

* `toc` - collects headings into a table of contents.

### `meta` Section

A blog post may have metadata - some additional invisible information associated with a blog post. The metadata is written using JSON format in the optional `meta` section:

    ### meta

        {
            "categories": ["drafts"]
            ...
        }

The `meta` section is not rendered on the blog post page.

Currently, only one metadata property is supported:

* `categories` (array of strings) - additionally assigned categories.
  See [Managing Blog Categories](../07/25-osmsoftware-managing-blog-categories.md)
  for more details.

### `meta.*` Sections
 
Alternatively, you can provide additional meta information in Markdown format in `meta.*` sections: For example, `list_text` field specifies text to be rendered on blog post list pages. 

    ### meta.list_text
    
    This very website, `osm.software`, is built using Osm Framework. 
    It's open-source, but before diving into implementation details, 
    let's review its initial requirements.

The `meta.*` sections are not rendered on the blog post page.

Currently, only two `meta.*` section are supported:

* `list_text` section specifies text to be rendered on blog post list pages. Don't use links in this section.
* `description` section specifies text to be rendered in blog post page's meta description that is shown on search engine result pages. Don't use Markdown formatting in this section. If omitted, `list_text` is used.

### Custom Metadata

You may provide custom properties in the `meta` section, and custom `meta.*` section. The system will read and store this metadata, and you can easily process it by customizing PHP code and templates. 

You can also use custom properties for your own internal information. For example, while writing a blog post, we put down a list of future blog post candidates in a `candidate_posts` property. Later, when deciding what to do next, we search through the blog post sources for this property.  

## Links And Images

### Links

Blog posts may contain relative links to other blog posts. The general rule is that links should work even if you click on them in the GitHub repository. It means that they should contain the exact filename to a referenced Markdown file:

    # link with a title
    see [Getting Started](../04/08-getting-started.md)

    # link without a title
    <../04/08-getting-started.md>

For non-blog post links, use absolute URLs:

    # link to an external website
    <https://www.php.net/>

### Images

Blog posts may contain relative links to images. By convention, images are stored in the same directory:

    # show an image from the current directory
    ![Welcome Screen](welcome-screen.png)

### Checking Broken links

Use `osm check:links` command to scan all the Markdown files, and check if all the relative links point to an existing blog post or image.

Use `osm check:links -x` command to check if both internal and external links point to existing pages.

