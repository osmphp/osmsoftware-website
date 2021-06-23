<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \My\Posts\Posts $posts */
?>
<x-base::layout title='Recent Posts | Blog | Osm Commerce'>
    <x-slot name="header">
        <x-posts::header />
    </x-slot>
    <section class="col-start-1 col-span-12 md:col-start-5 md:col-span-8 lg:col-start-4 md:col-span-9">
        <h1 class="text-2xl sm:text-4xl font-bold my-8">
            {{ \Osm\__("Latest Posts") }}
        </h1>
        <p>{{ \Osm\__("... directly from the team") }}</p>

        @forelse($posts->items as $post)
            <x-posts::list-item :post="$post" />
        @empty
            <p>{{ \Osm\__("No results found matching your selection.") }}</p>
        @endforelse
    </section>
    <section class="hidden md:block md:col-start-1 md:col-span-4 lg:col-span-3 row-start-1">
        <x-posts::applied_filters :posts="$posts"/>

        @foreach ($posts->filters as $filter)
            @if ($filter->component)
                <x-dynamic-component :component="$filter->component"
                    :filter="$filter" />
            @endif
        @endforeach
    </section>
</x-base::layout>