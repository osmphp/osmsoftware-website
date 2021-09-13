<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \My\Posts\Post $news */
?>
<x-std-pages::layout title='Osm Software' description="A website about Osm Framework -
    an open-source PHP 8 framework for creating modern Web applications that's
    insanely fast, unprecedentedly extensible, and fun to work with -
    and its ecosystem."
>
    <x-slot name="header">
        <x-posts::header/>
    </x-slot>
    <section class="col-span-12 my-6">
        <h1 class="text-2xl sm:text-4xl font-bold text-center">
            Tools For Better Developers
        </h1>
    </section>
    <section class="col-span-12 lg:col-start-4 lg:col-span-6 mb-6">
        <h2 class="text-xl sm:text-2xl font-bold text-center">
            Osm Framework
        </h2>
        <p class="text-lg mt-8">
            Osm Framework is an open-source, insanely fast, unprecedentedly
            extensible, and fun to work with PHP 8 framework for creating modern
            Web applications. It's built on top of tried and tested Symfony and
            Laravel components.
        </p>
        <p class="mt-8 text-center flex flex-wrap justify-center gap-2">
            <a class="bg-blue-800 hover:bg-blue-900 text-white py-2 px-4 rounded"
                href="{{ "{$osm_app->http->base_url}/blog/21/08/framework-installation.html" }}" title="Download"
            >Download</a>
            <a class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded"
                href="https://github.com/osmphp/framework#documentation" title="Docs"
            >Docs</a>
            <a class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded"
                href="{{ "{$osm_app->http->base_url}/blog/framework/" }}" title="Blog"
            >Blog</a>
            <a class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded"
                href="https://github.com/osmphp/framework" title="Source"
            >Source</a>
        </p>
    </section>

    <section class="col-span-12 lg:col-start-4 lg:col-span-6 mb-6">
        <h2 class="text-xl sm:text-2xl font-bold text-center">
            @if ($news->main_category_file)
                {!! $news->main_category_file->post_title_html !!}:
            @endif
            {{ $news->title }}
        </h2>
        <div class="mt-8 prose-lg max-w-none">
            {!! $news->list_html !!}
        </div>
        <p class="mt-8 text-center flex flex-wrap justify-center gap-2">
            <a class="bg-blue-800 hover:bg-blue-900 text-white py-2 px-4 rounded"
                href="{{ $news->url }}" title="Details"
            >Details</a>
            <a class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded"
                href="{{ "{$osm_app->http->base_url}/blog/news/" }}" title="Old News"
            >Old News</a>
        </p>
    </section>
    <section class="col-span-12 lg:col-start-4 lg:col-span-6 mb-6">
        <h2 class="text-xl sm:text-2xl font-bold text-center">
            Reference Project: osm.software
        </h2>
        <p class="text-lg mt-8">
            This very website is an open-source project built using Osm Framework. Explore it as
            a practical example of how various Osm Framework features can be
            used.
        </p>
        <p class="mt-8 text-center flex flex-wrap justify-center gap-2">
            <a class="bg-blue-800 hover:bg-blue-900 text-white py-2 px-4 rounded"
                href="{{ "{$osm_app->http->base_url}/blog/21/08/osmsoftware-installation.html" }}" title="Download"
            >Download</a>
            <a class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded"
                href="https://github.com/osmphp/osmsoftware-website#documentation" title="Docs"
            >Docs</a>
            <a class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded"
                href="{{ "{$osm_app->http->base_url}/blog/osmsoftware/" }}" title="Blog"
            >Blog</a>
            <a class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded"
                href="https://github.com/osmphp/osmsoftware-website" title="Source"
            >Source</a>
        </p>
    </section>
</x-std-pages::layout>