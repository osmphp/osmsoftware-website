<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \Osm\Docs\Docs\Page $page */
?>
<x-std-pages::layout :title='$page->title . " | Documentation | Osm Software"'
    :description="$page->meta_description"
>
    <div class="container mx-auto px-4 grid grid-cols-12">
        <article class="col-start-1 col-span-12 md:col-start-4 md:col-span-9">
            <h1 class="text-2xl sm:text-4xl pt-4 mb-4 border-t border-gray-300
                md:pl-4 xl:pr-4 text-{{ $page->version->book->color }}"
            >
                {{ $page->title }}
            </h1>
            @if ($page->url !== '/index.html')
                <p class="text-sm md:pl-4 xl:pr-4">
                    <a href="{{ $page->version->index_page->absolute_url }}"
                        title="{{ $page->version->index_page->title }}" class="link">
                        {{ $page->version->index_page->title }}</a>

                    @foreach ($page->parents as $parentPage)
                        ∙
                        <a href="{{ $parentPage->absolute_url }}"
                            title="{{ $parentPage->title }}" class="link">
                            {{ $parentPage->title }}</a>
                    @endforeach
                </p>
            @endif
            <p class="text-sm italic text-gray-400 md:pl-4 xl:pr-4">
                @if (count($page->version->book->versions) > 1)
                    {{ \Osm\__("Version") }} {{ $page->version->name }}
                    ∙
                @endif
                {{ $page->reading_time }}
            </p>
            <section class="prose max-w-none my-4 sm:text-lg md:pl-4 xl:pr-4">
                {!! $page->html !!}
            </section>
        </article>
        <aside class="left-drawer left-drawer--closed">
            <x-docs::tree :version="$page->version"/>
        </aside>
    </div>
</x-std-pages::layout>