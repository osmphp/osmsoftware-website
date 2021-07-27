<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
?>
<ul class="flex px-4 mb-4 bg-white">
    <li class="md:hidden" data-js-hamburger='{
        "$sidebar": ".left-drawer",
        "opened_class": "left-drawer--opened",
        "closed_class": "left-drawer--closed"
    }'>
        <button aria-label="{{ \Osm\__("Show/hide sidebar") }}"
            class="w-10 h-10 text-2xl flex items-center
                justify-center focus:outline-none"
        >
            <i class="fas fa-bars"></i>
        </button>
    </li>
    <li class="w-20 h-10 text-2xl flex items-center justify-center">
        <a href="{{ "{$osm_app->http->base_url}/" }}">OSM</a>
    </li>
    <li class="w-32 h-10 flex-grow flex items-center">
        <form action="{{ "{$osm_app->http->base_url}/blog/search" }}" class="flex-grow">
            <div class="flex border-b py-1 border-solid border-gray-500">
                <button aria-label="{{ \Osm\__("Search") }}" type="submit"
                    class="w-6 h-6 mr-2 flex items-center justify-center
                        focus:outline-none"
                >
                    <i class="fas fa-search"></i>
                </button>
                <input type="text" name="q"
                    placeholder="{{ \Osm\__("Search blog") }}"
                    class="w-20 flex-grow focus:outline-none"
                    value="{{ $osm_app->http->query['q'] ?? '' }}">
            </div>
        </form>
    </li>
    <li class="hidden">
        <a href="{{ "{$osm_app->http->base_url}/my/" }}"
           class="w-10 h-10 text-2xl flex items-center justify-center">
            <i class="fas fa-user"></i>
        </a>
    </li>
</ul>
