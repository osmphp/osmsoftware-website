<?php
/* @var \Osm\Docs\Docs\Page[] $pages */
?>
<ul>
    @foreach($pages as $page)
        <li>
            <div class="flex p-2 my-2 -mx-2 rounded-md text-white font-bold
                bg-{{ $page->version->book->color }}"
            >
                <a class="flex-grow" href="{{ $page->absolute_url}}"
                    title="{{ $page->title }}">
                    {{ mb_strtoupper($page->title) }}</a>
            </div>
            @if (!empty($page->children))
                <div class="-ml-4">
                    @include ('docs::tree-items', ['pages' => $page->children])
                </div>
            @endif
        </li>
    @endforeach
</ul>
