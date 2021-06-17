<?php

declare(strict_types=1);

namespace My\Posts;

use Carbon\Carbon;
use My\Markdown\File;
use My\Markdown\Exceptions\InvalidPath;
use My\Posts\Hints\Series;
use My\Posts\Hints\Tag;
use Osm\Core\App;
use Osm\Framework\Http\Http;
use function Osm\__;

/**
 * @property Tag[]|null $tags
 * @property ?Series $series
 * @property ?string $list_text
 * @property ?string $list_html
 * @property Carbon $created_at
 * @property string $url_key
 * @property string $url
 * @property Http $http
 */
class Post extends File
{
    protected function get_root_path(): string {
        global $osm_app; /* @var App $osm_app */

        return "{$osm_app->paths->data}/posts";
    }

    protected function get_created_at(): Carbon {
        $this->parsePath();
        return $this->created_at;
    }

    protected function parsePath(): void {
        if (!preg_match(static::PATH_PATTERN, $this->path, $match)) {
            throw new InvalidPath(__(
                "Blog post file paths are expected to be of 'YY/MM/DD-url-key.md', but ':path' is not.",
                ['path' => $this->path]));
        }

        $this->created_at = Carbon::createFromDate((int)"20{$match['year']}",
            (int)$match['month'], (int)$match['day']);
        $this->url_key = $match['url_key'];
    }

    protected function get_url(): string {
        return "{$this->http->base_url}/blog/" .
            "{$this->created_at->format("y/m")}/{$this->url_key}.html";
    }

    protected function get_http(): Http {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->http;
    }

    protected function get_tags(): ?array {
        if (empty($this->meta->tags)) {
            return null;
        }

        $tags = [];

        foreach ($this->meta->tags as $title) {
            $tags[] = (object)[
                'title' => $title,
                'url_key' => $this->generateId($title),
            ];
        }

        return $tags;
    }

    protected function get_series(): ?\stdClass {
        if (empty($this->meta->series)) {
            return null;
        }

        if (empty($this->meta->series_part)) {
            return null;
        }

        return (object)[
            'title' => $this->meta->series,
            'url_key' => $this->generateId($this->meta->series),
            'part' => $this->meta->series_part,
        ];
    }

    protected function get_list_text(): ?string {
        return $this->meta->list_text ?? null;
    }

    protected function get_list_html(): ?string {
        return $this->html($this->list_text);
    }
}