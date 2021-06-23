<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \My\Posts\Filter\Category $filter */
?>
<h2 class="text-xl font-bold mt-8 mb-4">{!! $filter->title_html !!}</h2>
<ul>
    @foreach($filter->items as $item)
        @if ($item->visible)
            <li>
                <a href="{{ $item->applied ? $item->remove_url : $item->add_url }}"
                    title="{{ $item->title }}"
                >
                    <span>
                        @if ($item->applied)
                            <i class="fas fa-check"></i>
                        @endif
                    </span>
                    {!! $item->title_html !!}
                    ({{ $item->count }})</a>
            </li>
        @endif
    @endforeach
</ul>
