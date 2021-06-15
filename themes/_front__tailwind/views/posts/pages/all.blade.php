<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \My\Posts\Posts $posts */
?>
<x-base::layout title='Blog | Osm Commerce'>
    <section class="col-start-1 col-span-12 md:col-start-4 md:col-span-9">
        <h1 class="text-2xl sm:text-4xl font-bold">
            Blog
        </h1>
        @foreach($posts->items as $post)
            <x-posts::list-item :post="$post" />
        @endforeach
    </section>
    <section class="hidden md:block md:col-start-1 md:col-span-3 row-start-1">
        <p>Navigation goes here ...</p>
    </section>
</x-base::layout>