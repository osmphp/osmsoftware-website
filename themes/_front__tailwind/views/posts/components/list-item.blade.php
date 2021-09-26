<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \Osm\Blog\Posts\Post $post */
?>
<article>
    @if ($post)
        <h2 class="text-lg  font-bold mt-8 underline my-5">
            <a href="{{ $post->url }}" title="{{ $post->title }}">
                @if ($post->main_category_file)
                    {!! $post->main_category_file->post_title_html !!}:
                @endif
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

            @foreach($post->category_files as $category)
                ∙

                <a href="{{ "{$osm_app->http->base_url}/blog/{$category->url_key}/" }}"
                    title="{{ $category->title }}" class="link">
                    {!! $category->title_html !!}</a>
            @endforeach
        </p>
        <p>{{ $post->created_at->diffForHumans() }} ∙ {{ $post->reading_time }}</p>
        @if ($post->list_html)
            <a href="{{ $post->url }}" title="{{ $post->title }}">
                <section class="prose max-w-none my-5">
                    {!! $post->list_html !!}
                </section>
            </a>
        @endif
    @endif
</article>
