<?php

declare(strict_types=1);

namespace My\Posts\Routes\Front;

use My\Posts\Post;
use My\Posts\Posts;
use Osm\Core\App;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Framework\Http\Exceptions\NotFound;
use Osm\Framework\Http\Route;
use Symfony\Component\HttpFoundation\Response;
use function Osm\view_response;

/**
 * @property string $root_path
 * @property string $image_path
 */
class RenderImage extends Route
{
    public function run(): Response {
        static $imageContentTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
        ];

        if (!(is_file($path = "{$this->root_path}/{$this->image_path}"))) {
            throw new NotFound();
        }

        $type = $imageContentTypes[strtolower(
            pathinfo($this->image_path, PATHINFO_EXTENSION)
        )];
        $name = basename($this->image_path);

        return new Response(file_get_contents($path), Response::HTTP_OK, [
            'content-type' => $type,
            'content-disposition' => 'inline; filename="'.$name.'"',
        ]);
    }

    protected function get_root_path(): string {
        global $osm_app; /* @var App $osm_app */

        return "{$osm_app->paths->data}/posts";
    }

}