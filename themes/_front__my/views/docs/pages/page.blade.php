<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \Osm\Docs\Docs\Page $page */
?>
<x-std-pages::layout :title='$page->title . " | Documentation | Osm Software"'
    :description="$page->meta_description"
>
    <div class="container mx-auto px-4 grid grid-cols-12">
        <article class="col-start-1 col-span-12 md:col-start-5 md:col-span-8
            xl:col-start-4 xl:col-span-6"
        >
            <h1 class="text-2xl sm:text-4xl pt-4 mb-4
                md:pl-4"
            >
                {{ $page->title }}
            </h1>
            <p class="text-sm italic text-gray-400 md:pl-4">
                {{ $page->reading_time }}
            </p>
            <section class="prose max-w-none my-4 sm:text-lg md:pl-4">
                {!! $page->html !!}
            </section>
        </article>
        {{--<aside class="left-drawer left-drawer--closed">
        </aside>--}}
        <aside class="hidden xl:block xl:col-start-10 xl:col-span-3 row-start-1">
        </aside>
    </div>
</x-std-pages::layout>