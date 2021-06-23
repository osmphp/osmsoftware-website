<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
?>
<ul class="flex px-4 mb-4 bg-white">
    <li>
        <button class="w-10 h-10 text-2xl flex items-center
            justify-center focus:outline-none"
        >
            <i class="fas fa-bars"></i>
        </button>
    </li>
    <li class="w-20 h-10 text-2xl flex items-center justify-center">
        OSM
    </li>
    <li class="w-32 h-10 flex-grow flex items-center">
        <form action="{{ "{$osm_app->http->base_url}/search" }}" class="flex-grow">
            <div class="flex border-b py-1 border-solid border-gray-500">
                <button type="submit"
                    class="w-6 h-6 mr-2 flex items-center justify-center
                        focus:outline-none"
                >
                    <i class="fas fa-search"></i>
                </button>
                <input type="text" name="q"
                    placeholder="{{ \Osm\__("Search blog (press '/')") }}"
                    class="w-20 flex-grow focus:outline-none">
            </div>
        </form>
    </li>
    <li>
        <a href="{{ "{$osm_app->http->base_url}/my/" }}"
           class="w-10 h-10 text-2xl flex items-center justify-center">
            <i class="fas fa-user"></i>
        </a>
    </li>
</ul>