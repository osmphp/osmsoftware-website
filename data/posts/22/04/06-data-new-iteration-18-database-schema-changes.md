# New Iteration "18 Database Schema Changes"

After finishing the iteration "#17 Minimum UI", I revisited the goals and the scope of the minimum viable product, and picked the most pressing task for the next iteration - "#18 Database Schema Changes".

Contents:
 
{{ toc }}

### meta.abstract

After finishing the iteration "#17 Minimum UI", I revisited the goals and the scope of the minimum viable product, and picked the most pressing task for the next iteration - "#18 Database Schema Changes".

## The End Of Iteration "17 Minimum UI"

There is always something left to do. Yet, I consider the current iteration "#17 Minimum UI" complete.

It has started with just an idea about [much easier data class syntax](../02/02-data-classes-revisited.md), then it developed into a [vision](../02/17-data-roadmap.md), and finally, it took 1.5 months of hard work. 

Now, it is a working proof-of-concept.

## MVP Goal

The goal is to create a minimum viable product (MVP) that you can rely on to: 

* solve real-world problems,
* make your solutions reusable and distribute them,
* become better over time.   

### Solving Real-World Problems

Once you have to deal with structured data, the first tool at your disposal is a spreadsheet application, for example, Microsoft Excel. It's a great solution in many cases, and, yet, in many cases, it's not. And when it's not, you need a solution based on a database. 
 
And Osm Admin is the ultimate database solution:

* With minimum programming skills, define data structure.
* Use a multi-user environment (the admin area) for entering and analyzing your data.
* Build a blazing-fast front area for exposing your data to the world.
* Integrate it with other software using the API.
* Customize and extend anything. Really anything.

For example, if you need a blog, you can define what a blog is in terms of data structures, build a front area displaying it, and start entering blog posts in the admin area, and showing them to the world in the front area.

There are many more possible examples: project management, customer relations, finance, content management, e-commerce, and other. 

### Reusable Solutions

Once the solution works for you, you can convert it into a reusable solution that also helps others.

Reusable solutions can be installed into any Osm Admin project, and work together.

For example, you can install a blogging and project management solutions developed by someone else into your projects, and they "just work".

### Getting Better Over Time

The sooner Osm Admin is out, the better. It means that it will miss some features.

The missing features will arrive into the existing projects later. Most will be backward compatible, some major changes will require some additional, well documented, effort. 

## MVP Scope

Osm Admin should have enough features for a blogging solution similar to this blog.

### Admin Features 

In addition to currently supported `string` type, it should support `float`, `int`, `bool` and record reference property types.

In addition to currently supported input and select controls, it should support image and Markdown controls.

There should be a menu for accessing list pages of every object type (blog posts, categories).
 
### Security

It should validate user input both on the server, and in the browser.

The admin area should be password-protected.

It should be protected against CSRF, XSS and other attacks. 

### Reliability

It should only allow schema syntax (class, grid, form and indexer attributes and property types) that is 100% supported. All the supported syntax should be documented.

It should adjust the database schema and preserve/convert existing data according to *any* changes in schema classes, grids, forms or indexers.

Data schema updates, indexing and other "magic" background features should provide logs that explain what they actually do.

Queries and other non-UI features should be unit-tested. 

Finally, I should deal with the [asynchronous nature of search indexes](05-data-object-deletion.md#dealing-with-async-nature-of-search-index).

### On-Boarding 

The website, GitHub, and Twitter: explain the value proposition, and provide an easy way to hop in.

The documentation, YouTube and Twitter: how-tos.

Easy to get started on a local machine, Vagrant, GitPod, GitHub Spaces. 

Clear how to get support, or becoming a contributor.

Discord as a community space.

### Discoverability

**Later**. Well, I'm still learning how to get to people that may actually find Osm Admin useful. 

## Scope Of Iteration "18 Database Schema Changes"

The common wisdom advises to tackle the hardest problems first. And the hardest problem is:

> It should adjust the database schema and preserve/convert existing data according to *any* changes in schema classes, grids, forms or indexers.

That's what I'm focusing on now.
 