<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
$theme_url = "{$osm_app->http->base_url}/{$osm_app->theme->name}";
?>
<header class="container mx-auto">
    <ul class="flex bg-white px-4">
        <li class="h-14 md:hidden" data-js-hamburger='{
            "$sidebar": ".left-drawer",
            "opened_class": "left-drawer--opened",
            "closed_class": "left-drawer--closed"
        }'>
            <button aria-label="{{ \Osm\__("Show/hide sidebar") }}"
                class="w-6 h-14 flex items-center justify-center
                    focus:outline-none text-gray-300 text-2xl"
            >
                <i class="icon-bars"></i>
            </button>
        </li>
        <li class="w-24 h-14">
            <a class="w-28 h-28 relative block -top-2"
               href="{{ "{$osm_app->http->base_url}/" }}"
            >
                <img src="{{ "{$theme_url}/images/theme/logo.png" }}"
                     class="" alt="Osm Software">
            </a>
        </li>
        <li class="w-32 h-14 flex-grow flex items-center">
            <form action="{{ "{$osm_app->http->base_url}/blog/search" }}"
                  class="flex-grow"
            >
                <div class="flex border-b border-gray-300 h-14">
                    <input type="text" name="q"
                        placeholder="{{ \Osm\__("Search") }}"
                        class="w-20 pl-4 pt-2 flex-grow focus:outline-none text-lg"
                        value="{{ $osm_app->http->query['q'] ?? '' }}">

                    <button aria-label="{{ \Osm\__("Search") }}" type="submit"
                        class="w-6 h-14 flex items-center justify-center
                            focus:outline-none text-gray-300 text-2xl"
                    >
                        <i class="icon-search"></i>
                    </button>
                </div>
            </form>
        </li>
    </ul>
    <img class="block mx-auto mt-12 pb-4 h-8
            xs:pb-0 xs:ml-32 xs:mr-6 xs:my-6 xs:h-auto
            md:h-8"
         src="{{ "{$theme_url}/images/theme/slogan.svg" }}"
         alt="{{ \Osm\__("Tools For Better Developers")}}">
</header>
