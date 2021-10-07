<?php
/* @var \Osm\Docs\Docs\Version $version */
/* @var \Osm\Docs\Docs\Page[] $top_level_pages */
?>
@if (count($top_level_pages))
    <nav>
        <h2 class="text-xl mt-8 text-{{ $version->book->color }}">
            <a href="{{ $version->index_page->absolute_url}}"
                title="{{ $version->index_page->title }}">
                {{ $version->index_page->title }}</a>
        </h2>
        @if (count($version->book->versions) > 1)
            <p class="text-sm mb-4">
                <label for="book-toc-version">{{ \Osm\__("Version:")}}</label>
                <select id="book-toc-version"
                    class="text-black border border-gray-300 bg-white px-2"
                    data-js-open-option-value='{}'
                >
                    @foreach ($version->book->versions as $bookVersion)
                        <option value="{{ $bookVersion->absolute_url}}/"
                            @if ($bookVersion === $version)selected @endif
                        >
                            {{ $bookVersion->name }}
                        </option>
                    @endforeach
                </select>
            </p>
        @endif
        @include ('docs::top-tree-items', ['pages' => $top_level_pages])
    </nav>
@endif
