<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \Osm\Blog\Posts\Post $post */
?>
<x-std-pages::layout :title='$post->title . ($post->main_category_file ? " | " . $post->main_category_file->post_title : "") . " | Blog | Osm Software"'
    :description="$post->meta_description"
>
    <div class="container mx-auto px-4 grid grid-cols-12">
        <article class="col-start-1 col-span-12 md:col-start-5 md:col-span-8
            xl:col-start-4 xl:col-span-6"
        >
            <h1 class="text-2xl sm:text-4xl pt-4 mb-4
                md:pl-4 text-{{ $post->main_category_file->color }}"
            >
                @if ($post->main_category_file)
                    {!! $post->main_category_file->post_title_html !!}:
                @endif

                {{ $post->title }}
            </h1>
            <p class="text-sm md:pl-4">
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
                        {!! $category->title_html !!}</a>
                @endforeach
            </p>
            <p class="text-sm italic text-gray-400 md:pl-4">
                {{ $post->created_at->diffForHumans() }} ∙ {{ $post->reading_time }}
            </p>
            <section class="prose max-w-none my-4 sm:text-lg md:pl-4">
                {!! $post->html !!}
            </section>
        </article>
        {{--<aside class="left-drawer left-drawer--closed">
        </aside>--}}
        <aside class="hidden xl:block xl:col-start-10 xl:col-span-3 row-start-1">
        </aside>
    </div>
</x-std-pages::layout>