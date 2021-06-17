<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \My\Posts\Posts $posts */
?>
<x-base::layout title='Recent Posts | Blog | Osm Commerce'>
    <x-slot name="header">
        <x-posts::header />
    </x-slot>
    <section class="col-start-1 col-span-12 md:col-start-4 md:col-span-9">
        <h1 class="text-2xl sm:text-4xl font-bold mt-8">
            {{ \Osm\__("Latest Posts") }}
        </h1>
        <p>{{ \Osm\__("... directly from the team") }}</p>
        @foreach($posts->items as $post)
            <x-posts::list-item :post="$post" />
        @endforeach
    </section>
    <section class="hidden md:block md:col-start-1 md:col-span-3 row-start-1">
        @if ($posts->categories)
            <h2 class="text-xl font-bold mt-8 mb-4">Categories</h2>
            <ul>
                @foreach($posts->categories as $category)
                    @if ($category->current)
                        <li class="font-bold">
                            {!! $category->title_html !!}
                            ({{ $category->count }})
                        </li>
                    @else
                        <li>
                            <a href="{{ $category->url }}"
                                title="{{ $category->title }}"
                            >
                                {!! $category->title_html !!}
                                ({{ $category->count }})</a>
                        </li>
                    @endif
                @endforeach
            </ul>
        @endif
    </section>
</x-base::layout>