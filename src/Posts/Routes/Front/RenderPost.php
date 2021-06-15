<?php

declare(strict_types=1);

namespace My\Posts\Routes\Front;

use My\Posts\MarkdownParser;
use My\Posts\Posts;
use Osm\Core\App;
use Osm\Framework\Http\Exceptions\NotFound;
use Osm\Framework\Http\Route;
use Symfony\Component\HttpFoundation\Response;
use function Osm\view_response;

/**
 * @property string $root_path
 * @property string $year
 * @property string $month
 * @property string $url_key
 */
class RenderPost extends Route
{
    public function run(): Response {
        foreach (glob("{$this->root_path}/" .
            "{$this->year}/{$this->month}/??-{$this->url_key}.md") as $path)
        {
            return view_response('posts::pages.post', [
                'post' => MarkdownParser::new([
                    'path' => mb_substr($path, mb_strlen("{$this->root_path}/")),
                ]),
            ]);
        }

        throw new NotFound();
    }

    protected function get_root_path(): string {
        global $osm_app; /* @var App $osm_app */

        return "{$osm_app->paths->data}/posts";
    }

}