<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \My\Posts\Posts $posts */
$title = \Osm\__(":year :month Posts", [
    'year' => $posts->page_type->year,
    'month' => \Carbon\Carbon::createFromDate($posts->page_type->year,
        $posts->page_type->month, 1)->format('F'),
]);
?>
<x-std-pages::layout :title="$title . ' | Blog | Osm Software'">
    <div class="container mx-auto px-4 grid grid-cols-12 gap-4">
        <section class="col-start-1 col-span-12 md:col-start-4 md:col-span-9">
            <h1 class="text-2xl sm:text-4xl font-bold my-8">
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