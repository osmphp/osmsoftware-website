<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
$categories = $osm_app->modules[\Osm\Blog\Categories\Module::class]->categories;
/* @var \Osm\Blog\Categories\Category $category */

/* @var \Osm\Blog\Posts\Post $news */
?>
<x-std-pages::layout title='Osm Software' description="A website about Osm Framework -
    an open-source PHP 8 framework for creating modern Web applications that's
    insanely fast, unprecedentedly extensible, and fun to work with -
    and its ecosystem."
>
    <div class="container mx-auto px-4 grid grid-cols-12 gap-4">
        <?php $category = $categories['framework']; ?>
        <section class="col-span-12 lg:col-start-1 lg:col-span-6 mb-6">
            <h1 class="text-2xl sm:text-4xl text-center mt-16
                text-{{ $category->color }}"
            >
                Osm Framework
            </h1>
            <p class="text-lg mt-8">
                Osm Framework is an open-source, insanely fast, unprecedentedly
                extensible, and fun to work with PHP 8 framework for creating modern
                Web applications. It's built on top of tried and tested Symfony and
                Laravel components.
            </p>
            <p class="mt-8 text-center flex flex-wrap justify-center gap-2 text-white">
                <a class="py-2 px-4 rounded bg-gray-700 hover:bg-black"
                    href="{{ "{$osm_app->http->base_url}/blog/21/08/framework-installation.html" }}" title="Download"
                >Download</a>
                <a class="py-2 px-4 rounded bg-{{ $category->color }}
                    hover:bg-{{ $category->hover_color }}"
                    href="{{ "{$osm_app->http->base_url}/docs/framework/" }}" title="Docs"
                >Docs</a>
                <a class="py-2 px-4 rounded bg-{{ $category->color }}
                    hover:bg-{{ $category->hover_color }}"
                    href="{{ "{$osm_app->http->base_url}/blog/framework/" }}" title="Blog"
                >Blog</a>
                <a class="py-2 px-4 rounded bg-{{ $category->color }}
                    hover:bg-{{ $category->hover_color }}"
                    href="https://github.com/osmphp/framework" title="Source"
                >Source</a>
            </p>
        </section>

        <?php $category = $categories['data']; ?>
        <section class="col-span-12 lg:col-start-7 lg:col-span-6 mb-6">
            <h2 class="text-2xl sm:text-4xl text-center mt-16
                text-{{ $category->color }}">
                Osm Admin
            </h2>
            <p class="text-lg mt-8">
                Previously known under *The Big Red Button* codename, it's a package
                for defining application data structures and logic using PHP 8 classes and attributes,
                pressing *the big red button*, and getting fully functioning Admin Panel and API.
            </p>
            <p class="mt-8 text-center flex flex-wrap justify-center gap-2 text-white">
                <a class="py-2 px-4 rounded bg-{{ $category->color }}
                    hover:bg-{{ $category->hover_color }}"
                    href="{{ "{$osm_app->http->base_url}/blog/data/" }}" title="Blog"
                >Blog</a>
                <a class="py-2 px-4 rounded bg-{{ $category->color }}
                    hover:bg-{{ $category->hover_color }}"
                    href="https://github.com/osmphp/data" title="Source"
                >Source</a>
            </p>
        </section>

        @if ($news)
            <?php $category = $categories['news']; ?>
            <section class="col-span-12 lg:col-start-1 lg:col-span-6 mb-6">
                <h2 class="text-2xl sm:text-4xl text-center mt-16
                    text-{{ $category->color }}">
                    @if ($news->main_category_file)
                        {!! $news->main_category_file->post_title_html !!}:
                    @endif
                    {{ $news->title }}
                </h2>
                <div class="mt-8 prose-lg max-w-none">
                    {!! $news->abstract_html !!}
                </div>
                <p class="mt-8 text-center flex flex-wrap justify-center gap-2 text-white">
                    <a class="py-2 px-4 rounded bg-gray-700 hover:bg-black"
                        href="{{ $news->url }}" title="Details"
                    >Details</a>
                    <a class="py-2 px-4 rounded bg-{{ $category->color }}
                        hover:bg-{{ $category->hover_color }}"
                        href="{{ "{$osm_app->http->base_url}/blog/news/" }}" title="Old News"
                    >Old News</a>
                </p>
            </section>
        @endif

        <?php $category = $categories['osmsoftware']; ?>
        <section class="col-span-12 lg:col-start-7 lg:col-span-6 mb-6">
            <h2 class="text-2xl sm:text-4xl text-center mt-16
                text-{{ $category->color }}">
                Reference Project: osm.software
            </h2>
            <p class="text-lg mt-8">
                This very website is an open-source project built using Osm Framework. Explore it as
                a practical example of how various Osm Framework features can be
                used.
            </p>
            <p class="mt-8 text-center flex flex-wrap justify-center gap-2 text-white">
                <a class="py-2 px-4 rounded bg-gray-700 hover:bg-black"
                    href="{{ "{$osm_app->http->base_url}/blog/21/08/osmsoftware-installation.html" }}" title="Download"
                >Download</a>
                <a class="py-2 px-4 rounded bg-{{ $category->color }}
                    hover:bg-{{ $category->hover_color }}"
                    href="{{ "{$osm_app->http->base_url}/blog/osmsoftware/" }}" title="Blog"
                >Blog</a>
                <a class="py-2 px-4 rounded bg-{{ $category->color }}
                    hover:bg-{{ $category->hover_color }}"
                    href="https://github.com/osmphp/osmsoftware-website" title="Source"
                >Source</a>
            </p>
        </section>
    </div>
</x-std-pages::layout>