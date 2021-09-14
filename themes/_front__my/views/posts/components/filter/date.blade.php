<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
/* @var \My\Posts\Filter\Date $filter */
?>
<h2 class="text-xl font-bold mt-8 mb-4">{!! $filter->title_html !!}</h2>
<ul>
    @foreach($filter->items as $yearItem)
        <li>
            <a href="{{ $yearItem->applied
                ? $yearItem->remove_url
                : $yearItem->add_url }}"

                title="{{ $yearItem->title }}" class="block pl-6 relative"
            >
                <span class="absolute left-0">
                    <i class="{{ $yearItem->applied ? 'icon-v' : 'icon-box' }}"></i>
                </span>
                <span class="font-bold">
                    {!! $yearItem->title_html !!}
                    ({{ $yearItem->count }})
                </span></a>
            <ul class="grid grid-cols-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach($yearItem->months as $monthItem)
                    <li>
                        @if ($monthItem->count)
                            <a href="{{ $monthItem->applied
                                ? $monthItem->remove_url
                                : $monthItem->add_url }}"

                                title="{{ $monthItem->title }}"
                                class="block pl-6 relative"
                            >
                                <span class="absolute left-0">
                                    <i class="{{ $monthItem->applied ? 'icon-v' : 'icon-box' }}"></i>
                                </span>
                                <span>
                                    {!! $monthItem->title_html !!}
                                    ({{ $monthItem->count }})
                                </span></a>
                        @else
                            <div class="block pl-6 relative text-gray-400">
                                <span>
                                    {!! $monthItem->title_html !!}
                                </span>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </li>
    @endforeach
</ul>
