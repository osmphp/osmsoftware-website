<?php
global $osm_app; /* @var \Osm\Core\App $osm_app */
$theme_url = "{$osm_app->http->base_url}/{$osm_app->theme->name}";
$favicon_url = "$theme_url/images/theme/favicon";
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne+Mono&family=Titillium+Web:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

<link rel="apple-touch-icon" sizes="180x180" href="{{ $favicon_url }}/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="{{ $favicon_url }}/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="{{ $favicon_url }}/favicon-16x16.png">
<link rel="manifest" href="{{ $favicon_url }}/site.webmanifest">