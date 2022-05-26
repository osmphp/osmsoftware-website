# New Iteration "19 Developer Experience". Teamwork. Project Overview

Yesterday started as usual, planning new iteration, and then BAM! - one folk - Saif - joined the project. It made me correct the course a bit, and take care of potential contributors:

* `README` explains what the project is about, and how to get started as a contributor;
* I'm fixing things to make the project actually runnable just after installing it. 
 
Here is how the day went:

{{ toc }}

### meta.abstract

Yesterday started as usual, planning new iteration, and then BAM! - one folk - Saif - joined the project. It made me correct the course a bit, and take care of potential contributors:

* `README` explains what the project is about, and how to get started as a contributor;
* I'm fixing things to make the project actually runnable just after installing it.

## Iteration Goal 

In the new iteration, I want to concentrate on the best possible developer experience. Creating a new project, creating new table, adding `string` properties, deploying to production should be as easy and as fun as possible. 

The goal is to shoot a video that shows how much fun it is to create applications using Osm Admin.

**Later**. After this iteration, there will still be critical things to implement: more data types, more functions, security concerns, schema and input validation, computed and overridable properties, thorough test suites, async indexers, documentation, and more. See also: 

* [Class Syntax](../02/02-data-classes-revisited.md)
* [Roadmap](../02/17-data-roadmap.md)
* [Iteration 18](../04/06-data-new-iteration-18-database-schema-changes.md)

## New Team Member!

### Welcome, Saif!

Before going any deeper into coding, I have to say something important.

[Saif ur rehman Awan](https://twitter.com/awannsaif) contacted me, and after some chat, decided to join me on Osm Admin project. Welcome aboard!

I'm so excited! And I hope it really works well for all.

### Teamwork Goals

I want everyone involved in this project to feel at home with the project vision, the current status, the tasks at hand, the codebase and everything else.

I want to organize the project so that it's fun, everyone is the best version of oneself, busy with meaningful things but not overwhelmed, contributes only what they want, and yet, things are addressed according to their priority.

I've been a team lead before, but not in the open source world, and this feels like a whole new experience to me.

Yet, the on-boarding of a new member without prior preparation seems like a perfect opportunity to have a real-life check of what is really important/confusing/exciting about the project. 

Let's think what it takes to on-board a new team member.

### Project Overview

The first thing to do it to give concise overview of what the project is about and how it works, and a README is a perfect fit for that.

[DONE](https://github.com/osmphp/admin).

### Installing On Local Machine

Then, it's important to install the project on local machine, put it under debugger, and tinker around.

Again, a clear instruction is needed in README.

[DONE](https://github.com/osmphp/admin).

## Making Project Runnable

Before going into simplifying the installation of a new project, there are certain things that just don't work:

1. Migrations should run on page refresh.
2. Search index should be created/dropped in the indexer not in migrations.
3. Sample classes should only contain properties that are actually supported.
4. All test suites should pass. 

### Pruning Sample Classes

I deleted all sample data classes except `Product`:

    // samples/Products/Product.php
    /**
     * @property string $color #[Option(Color::class), Faceted]
     *
     * @uses Option, Faceted, Color
     */
    class Product extends Record
    {
    }
    
    // samples/Products/Color.php
    class Color extends Option
    {
        #[Title('Pinky')]
        const PINK = 'pink';
    
        const BLUE = 'blue';
    
        const WHITE = 'white';
    
        const BLACK = 'black';
    }
    
### Running Migrations On Page Refresh

Implementation:

    /**
     * @property Schema $schema #[Cached('schema')]
     *
    * @uses Cached
     */
    #[UseIn(App::class)]
    trait AppTrait
    {
        protected function get_schema(): Schema {
            $this->schema = Schema::new()->parse();
            $this->schema->migrate();
            return $this->schema;
        }
    }

Here is how it works.

Normally, the schema is unserialized from the cache, and no migration check occurs. 

However, if you change a data class, `gulp watch` (which should be always running) determines a file change and clears all cache entries including the schema.

And if schema is not found in cache, it's loaded from the source files in the `parse()` method, and compared to the existing schema and, if needed, migrated in the `migrate()` method.

Neat!

