<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
$theme_url = "{$osm_app->http->base_url}/{$osm_app->theme->name}";
?>
<x-std-pages::layout title='Page not found | Osm Software'>
    <div class="container mx-auto px-4">
        <h1 class="text-2xl sm:text-4xl pt-6 mb-8 text-center border-t border-gray-300">
            {{ \Osm\__("The website is being updated")}}
        </h1>
        <img class="block mx-auto"
             src="{{ "{$theme_url}/images/theme/not-so-osm.png" }}"
             alt="{{ \Osm\__("Not so Osm")}}">
    </div>
</x-std-pages::layout>