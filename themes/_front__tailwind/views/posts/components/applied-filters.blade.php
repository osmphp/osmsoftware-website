<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \My\Posts\Posts $posts */
?>
@if (count($posts->applied_filters))
    <h2 class="text-xl font-bold mt-8 mb-4">
        {{ \Osm\__("Applied Filters") }}
    </h2>
    <ul class="flex flex-wrap">
        @foreach ($posts->applied_filters as $appliedFilter)
            <li class="mr-4">
                {{--<span>{!! $appliedFilter->title_html !!}</span>:--}}
                <span>{!! $appliedFilter->value_html !!}</span>
                <a href="{{ $appliedFilter->clear_url }}"
                    title="{{ \Osm\__("Clear") }}"
                >
                    <i class="far fa-times-circle"></i></a>
            </li>
        @endforeach
    </ul>
    <p class="mt-4">
        <a href="{{ $posts->url()->removeAllFilters() }}"
            title="{{ \Osm\__("Clear all") }}" class="link"
        >
            {{ \Osm\__("Clear all") }}</a>
    </p>
@endif
