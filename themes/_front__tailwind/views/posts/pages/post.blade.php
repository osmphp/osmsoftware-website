<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \My\Posts\Post $post */
?>
<x-base::layout :title='$post->title . " | Blog | Osm Commerce"'>
    <x-slot name="header">
        <x-posts::header />
    </x-slot>
    <article class="col-start-1 col-span-12 md:col-start-4 md:col-span-9
        xl:col-start-4 xl:col-span-6"
    >
        <h1 class="text-2xl sm:text-4xl font-bold my-8">
            {{ $post->title }}
        </h1>
        <p>
            <a href="{{ "{$osm_app->http->base_url}/blog/{$post->created_at->year}/" }}"
                title="{{ $post->created_at->year }}" class="link">
                {{ $post->created_at->year }}</a>

            ∙

            <a href="{{ "{$osm_app->http->base_url}/blog/{$post->created_at->format('Y/m')}/" }}"
                title="{{ $post->created_at->format('F') }}" class="link">
                {{ $post->created_at->format('F') }}</a>

            @foreach($post->category_files as $category)
                ∙

                <a href="{{ "{$osm_app->http->base_url}/blog/{$category->url_key}/" }}"
                    title="{{ $category->title }}" class="link">
                    {{ $category->title_html }}</a>
            @endforeach
        </p>
        <p>{{ $post->created_at->diffForHumans() }}</p>
        <section class="prose max-w-none my-5">
            {!! $post->html !!}
        </section>
    </article>
    <section class="hidden md:block md:col-start-1 md:col-span-3 row-start-1">
    </section>
    <section class="hidden xl:block xl:col-start-10 xl:col-span-3 row-start-1">
    </section>
</x-base::layout>