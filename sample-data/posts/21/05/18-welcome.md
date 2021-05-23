# Welcome!

Welcome to this blog!

Here you will hear the news about the Osm Commerce project, read about how it actually works, know what's currently being built, and how.

{{ toc }}

## Why I care (and you should, too)

This part is about why I decided to take on this project. Usually, I don't use so many "I", but this part is very personal.

For the last ten years I've been developing and selling Magento extensions. 

Before that, I worked for various software companies, small and big, in various positions. I didn't even consider that being an independent developer is a sound career choice. Some of my former colleagues started freelancing, some started their own companies - however, they all did the same project work: searched for customers, made software to fulfill their needs, and got paid.

Being an independent developer was quite different. The beginning was hard - we had to put a lot of effort upfront. Yet, it was definitely worth it - as the sales are kind of independent of the time spent, sometimes it felt like I'm dreaming - money came into my account "while I slept". 

After some time, the experience became a lot worse. I'm not going to complain about the details, let's just say that the platform became a lot harder to work with, to the point that it was not fun anymore. 

So I decided to build a brand-new e-commerce platform, the one where a developer can earn decent money, and have fun. 

Currently, I'm focused on what developers need, not only to enjoy building things, but selling them, too. Later, more features will come that will make it a perfect choice for any store owner.

## Project status

Currently, the project consists of several packages. Every package higher in the list uses the bits from lower listed packages:

* [`osmphp/shop`](https://github.com/osmphp/shop) - the e-commerce platform, it's mostly not started.
* [`osmphp/data`](https://github.com/osmphp/data) - reusable modules for data-intensive applications, this one is in active development right now.
* [`osmphp/framework`](https://github.com/osmphp/framework) - reusable modules for building any Web application. This package heavily relies on battle-tested components from Symfony and Laravel frameworks. It's also in active development.
* [`osmphp/core`](https://github.com/osmphp/core) - a library for building highly extensible PHP code. This package is mostly ready, though more features will be added in the future.

Most of new code is unit-tested, both locally and on a CI server.

## Creating this website

In the end, this very website will be built with Osm Commerce. Temporarily, until `data` and `shop` packages are ready, I'll build it using the `framework` package.

I'll document this effort in this blog, so you'll get better understanding of how various framework modules can be used.

## Writing documentation

Sadly enough, nothing is documented at the moment. The lack of documentation is another reason for the blog. First, the features will be introduced here, and then the documentation will be written, based on the blog posts.

