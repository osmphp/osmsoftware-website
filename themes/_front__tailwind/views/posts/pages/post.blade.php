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
        <p>{{ $post->created_at->format('d M Y') }}</p>
        @if ($post->tags)
            <p>
                {{ \Osm\__("Tags") }}:
                @foreach($post->tags as $tag)
                    <a href="{{ "{$osm_app->http->base_url}/tags/{$tag->url_key}/" }}"
                       title="{{ $tag->title }}" class="link">{{ $tag->title }}
                    </a>
                    @if (!$loop->last), @endif
                @endforeach
            </p>
        @endif
        @if ($post->series)
            <p>
                {{ \Osm\__("Part :part in ", ['part' => $post->series->part]) }}

                <a href="{{ "{$osm_app->http->base_url}/series/{$post->series->url_key}/" }}"
                   title="{{ $post->series->title }}" class="link">
                    {{ $post->series->title }}</a>

                {{ \Osm\__("series") }}
            </p>
        @endif
        <section class="prose max-w-none my-5">
            {!! $post->html !!}
        </section>
    </article>
    <section class="hidden md:block md:col-start-1 md:col-span-3 row-start-1">
        <p>Navigation goes here ...</p>
    </section>
    <section class="hidden xl:block xl:col-start-10 xl:col-span-3 row-start-1">
        <p>Navigation goes here ...</p>
    </section>
</x-base::layout>