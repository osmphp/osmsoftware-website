<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \My\Posts\Posts $posts */
$title = \Osm\__(":year Posts", ['year' => $posts->page_type->year]);
?>
<x-std-pages::layout :title="$title . ' | Blog | Osm Software'">
    <div class="container mx-auto px-4 grid grid-cols-12">
        <section class="col-start-1 col-span-12 md:col-start-5 md:col-span-8">
            <h1 class="text-2xl sm:text-4xl pt-4 mb-4 border-t border-gray-300 md:pl-4">
                {{ $title }}
            </h1>

            @forelse($posts->items as $post)
                <x-posts::list-item :post="$post" />
            @empty
                <p class="my-4">
                    {{ \Osm\__("No posts match your selection.") }}
                </p>
            @endforelse
        </section>
        <aside class="left-drawer left-drawer--closed">
            <x-posts::applied_filters :posts="$posts"/>

            @foreach ($posts->filters as $filter)
                @if ($filter->visible)
                    <x-dynamic-component :component="$filter->component"
                        :filter="$filter" />
                @endif
            @endforeach
        </aside>
    </div>
</x-std-pages::layout>