<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \My\Posts\Post $post */
?>
<article>
    <h2 class="text-lg  font-bold mt-8 underline my-5">
        @if ($post->main_category_file)
            <a href="{{ "{$osm_app->http->base_url}/blog/{$post->main_category_file->url_key}/" }}"
                title="{{ $post->main_category_file->title }}" class="link"
            >{!!
                $post->main_category_file->title_html !!}</a><span class="font-normal">:</span>
        @endif

        <a href="{{ $post->url }}" title="{{ $post->title }}">
            {{ $post->title }}
        </a>
    </h2>
    <p>
        <a href="{{ "{$osm_app->http->base_url}/blog/{$post->created_at->year}/" }}"
            title="{{ $post->created_at->year }}" class="link">
            {{ $post->created_at->year }}</a>

        ∙

        <a href="{{ "{$osm_app->http->base_url}/blog/{$post->created_at->format('Y/m')}/" }}"
            title="{{ $post->created_at->format('F') }}" class="link">
            {{ $post->created_at->format('F') }}</a>

        @foreach($post->additional_category_files as $category)
            ∙

            <a href="{{ "{$osm_app->http->base_url}/blog/{$category->url_key}/" }}"
                title="{{ $category->title }}" class="link">
                {!! $category->title_html !!}</a>
        @endforeach
    </p>
    <p>{{ $post->created_at->diffForHumans() }}</p>
    @if ($post->list_html)
        <a href="{{ $post->url }}" title="{{ $post->title }}">
            <section class="prose max-w-none my-5">
                {!! $post->list_html !!}
            </section>
        </a>
    @endif
</article>
