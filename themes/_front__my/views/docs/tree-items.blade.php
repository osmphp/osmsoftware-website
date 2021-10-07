<?php
/* @var \Osm\Docs\Docs\Page[] $pages */
?>
<ul class="ml-4">
    @foreach($pages as $page)
        <li>
            <div class="flex">
                <a class="link flex-grow" href="{{ $page->absolute_url}}"
                    title="{{ $page->title }}">
                    {!! $page->title_html !!}</a>
            </div>
            @if (!empty($page->children))
                @include ('docs::tree-items', ['pages' => $page->children])
            @endif
        </li>
    @endforeach
</ul>
