<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
$theme_url = "{$osm_app->http->base_url}/{$osm_app->theme->name}";
?>
<x-std-pages::layout title='Error | Osm Software'>
    <div class="container mx-auto px-4">
        <h1 class="text-2xl sm:text-4xl pt-6 mb-8 text-center border-t border-gray-300">
            {{ \Osm\__("Something bad happened")}}
        </h1>
        <p class="my-8 sm:text-lg text-center">
            {{ \Osm\__("... but we are working on it.")}}
        </p>
        <img class="block mx-auto"
             src="{{ "{$theme_url}/images/theme/not-so-osm-2.png" }}"
             alt="{{ \Osm\__("Not so Osm")}}">
         @if($content)
            <section class="prose max-w-none my-8 sm:text-lg">
                <pre><code>{{ $content }}</code></pre>
            </section>
         @endif
    </div>
</x-std-pages::layout>