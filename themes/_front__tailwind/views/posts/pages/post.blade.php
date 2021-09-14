<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \My\Posts\Post $post */
?>
<x-std-pages::layout :title='$post->title . ($post->main_category_file ? " | " . $post->main_category_file->post_title : "") . " | Blog | Osm Software"'
    :description="$post->meta_description"
>
    <div class="container mx-auto px-4 grid grid-cols-12 gap-4">
        <article class="col-start-1 col-span-12 md:col-start-4 md:col-span-9
            xl:col-start-4 xl:col-span-6"
        >
            <h1 class="text-2xl sm:text-4xl font-bold my-8">
                @if ($post->main_category_file)
                    {!! $post->main_category_file->post_title_html !!}:
                @endif

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
                        {!! $category->title_html !!}</a>
                @endforeach
            </p>
            <p>{{ $post->created_at->diffForHumans() }} ∙ {{ $post->reading_time }}</p>
            <section class="prose max-w-none my-5">
                {!! $post->html !!}
            </section>
        </article>
        {{--<aside class="left-drawer left-drawer--closed">
        </aside>--}}
        <aside class="hidden xl:block xl:col-start-10 xl:col-span-3 row-start-1">
        </aside>
    </div>
</x-std-pages::layout>