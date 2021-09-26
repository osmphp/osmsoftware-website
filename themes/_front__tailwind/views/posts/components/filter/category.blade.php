<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \Osm\Blog\Posts\Filter\Category $filter */
?>
<h2 class="text-xl font-bold mt-8 mb-4">{!! $filter->title_html !!}</h2>
<ul>
    @foreach($filter->items as $item)
        @if ($item->visible)
            <li>
                <a href="{{ $item->applied ? $item->remove_url : $item->add_url }}"
                    title="{{ $item->title }}" class="block pl-6 relative"
                >
                    <span class="absolute left-0">
                        @if ($item->applied)
                            <i class="fas fa-check"></i>
                        @endif
                    </span>
                    <span>
                        {!! $item->title_html !!}
                        ({{ $item->count }})
                    </span></a>
            </li>
        @endif
    @endforeach
</ul>
