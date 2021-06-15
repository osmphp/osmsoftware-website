<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \My\Posts\MarkdownParser $post */
?>
<article>
    <h2 class="text-lg  font-bold mt-8 underline">
        <a href="{{ $post->url }}" title="{{ $post->title }}">
            {{ $post->title }}
        </a>
    </h2>
    <p>{{ $post->created_at->format('d M Y') }}</p>
    @if ($post->tags)
        <p>
            {{ \Osm\__("Tags") }}:
            @foreach($post->tags as $tag)
                <a href="{{ "{$osm_app->http->base_url}/tags/{$tag->url_key}/" }}"
                   title="{{ $tag->title }}" class="link">
                    {{ $tag->title }}</a>
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
    @if ($post->list_html)
        <section class="prose max-w-none my-5">
            {!! $post->list_html !!}
        </section>
        <p>
            <a href="{{ $post->url }}" title="{{ $post->title }}" class="link">
                {{ \Osm\__("Read more") }}</a>
        </p>
    @endif
</article>
