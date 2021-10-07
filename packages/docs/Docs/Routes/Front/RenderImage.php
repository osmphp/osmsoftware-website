<?php

namespace Osm\Docs\Docs\Routes\Front;

use Osm\Core\Exceptions\NotImplemented;
use Osm\Docs\Docs\Page;
use Osm\Framework\Http\Exceptions\NotFound;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property string $path
 */
class RenderImage extends VersionRoute
{
    public function run(): Response {
        static $imageContentTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
        ];

        if (!($path = $this->find())) {
            throw new NotFound();
        }


        $type = $imageContentTypes[strtolower(
            pathinfo($path, PATHINFO_EXTENSION)
        )];
        $name = basename($path);

        return new Response(file_get_contents($path), Response::HTTP_OK, [
            'content-type' => $type,
            'content-disposition' => 'inline; filename="'.$name.'"',
        ]);
    }

    protected function find(): ?string {
        if (($pos = mb_strrpos($this->path, '/')) === false) {
            return is_file($path = "{$this->version->root_path}/{$this->path}")
                ? $path
                : null;
        }

        $pattern = $this->version->root_path . '/' .
            implode('/', array_map(
                fn(string $path) => "*{$path}",
                explode('/', mb_substr($this->path, 0, $pos))));
        $regex = '|^' . implode('/', array_map(
            fn(string $path) => '([0-9]+-)?' . preg_quote($path, '|'),
            explode('/', mb_substr($this->path, 0, $pos)))) . '$|u';

        foreach (glob($pattern, GLOB_ONLYDIR) as $path) {
            $relativePath = str_replace('\\', '/', mb_substr($path,
                mb_strlen($this->version->root_path) + 1));

            if (!preg_match($regex, $relativePath)) {
                continue;
            }

            if (is_file($filename = $path . mb_substr($this->path, $pos))) {
                return $filename;
            }
        }

        return null;
    }
}
