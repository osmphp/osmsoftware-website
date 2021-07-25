<?php

declare(strict_types=1);

namespace My\Categories;

use My\Markdown\Exceptions\InvalidPath;
use My\Markdown\File;
use Osm\Core\App;
use Osm\Framework\Http\Http;
use function Osm\__;
use Osm\Core\Attributes\Serialized;

/**
 * @property int $sort_order #[Serialized]
 * @property string $url_key #[Serialized]
 * @property string $post_title_html #[Serialized]
 * @property Http $http
 */
class Category extends File
{
    const PATH_PATTERN = '|^(?<sort_order>[0-9]+)-(?<url_key>.*)\.md$|u';

    protected function get_root_path(): string {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->modules[Module::class]->root_path;
    }

    protected function get_sort_order(): int {
        $this->parsePath();
        return $this->sort_order;
    }

    protected function get_url_key(): string {
        $this->parsePath();
        return $this->url_key;
    }

    protected function parsePath(): void {
        if (!preg_match(static::PATH_PATTERN, $this->path, $match)) {
            throw new InvalidPath(__(
                "Blog category file paths are expected to be of 'sort_order-url_key.md', but ':path' is not.",
                ['path' => $this->path]));
        }

        $this->sort_order = (int)$match['sort_order'];
        $this->url_key = $match['url_key'];
    }

    protected function get_post_title_html(): ?string {
        return $this->meta?->post_title ?? $this->title_html;
    }

    public function url(): string {
        return "{$this->http->base_url}/blog/" .
            "{$this->url_key}/";
    }

    protected function get_http(): Http {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->http;
    }
}