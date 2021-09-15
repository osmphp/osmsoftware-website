<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \My\Posts\Posts $posts */
?>
@if (count($posts->applied_filters))
    <h2 class="text-xl mt-8 mb-4">
        {{ \Osm\__("Applied Filters") }}
    </h2>
    <ul class="flex flex-wrap">
        @foreach ($posts->applied_filters as $appliedFilter)
            <li class="mr-4">
                <a href="{{ $appliedFilter->clear_url }}"
                    title="{{ \Osm\__("Clear") }}"
                >
                    <i class="icon-x"></i></a>
                <span>{!! $appliedFilter->value_html !!}</span>
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
