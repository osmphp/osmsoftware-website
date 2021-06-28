<?php

declare(strict_types=1);

namespace My\Posts;

use Carbon\Carbon;
use My\Categories\Category;
use My\Categories\Module as CategoryModule;
use My\Markdown\File;
use My\Markdown\Exceptions\InvalidPath;
use Osm\Core\App;
use Osm\Core\BaseModule;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Framework\Http\Http;
use function Osm\__;

/**
 * @property ?string $list_text
 * @property ?string $list_html
 * @property Carbon $created_at
 * @property string $url_key
 * @property string $url
 * @property Http $http
 * @property ?string $category
 * @property string[] $categories
 * @property Category[] $category_files
 * @property CategoryModule $category_module
 */
class Post extends File
{
    const PATH_PATTERN = '|^(?<year>[0-9]{2})/(?<month>[0-9]{2})/(?<day>[0-9]{2})-(?<url_key>.*)\.md$|u';

    // [title](url)
    const LINK_PATTERN_1 = '/\[(?<title>[^]]*)\]\((?<url>[^)]*)\)/u';

    // <url>
    const LINK_PATTERN_2 = '/\<(?<url>[^>]*)\>/u';

    protected function get_root_path(): string {
        global $osm_app; /* @var App $osm_app */

        return "{$osm_app->paths->data}/posts";
    }

    protected function get_created_at(): Carbon {
        $this->parsePath();
        return $this->created_at;
    }

    protected function get_url_key(): string {
        $this->parsePath();
        return $this->url_key;
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

    protected function get_list_text(): ?string {
        return $this->meta->list_text ?? null;
    }

    protected function get_list_html(): ?string {
        return $this->html($this->list_text);
    }

    protected function get_category(): ?string {
        foreach ($this->category_module->categories as $category) {
            if (str_starts_with($this->url_key, $category->url_key . '-')) {
                return $category->url_key;
            }
        }

        return null;
    }

    protected function get_categories(): array {
        $categories = $this->meta->categories ?? [];

        if ($this->category) {
            array_unshift($categories, $this->category);
        }

        return array_unique($categories);
    }

    protected function get_category_module(): CategoryModule|BaseModule {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->modules[CategoryModule::class];
    }

    protected function get_category_files(): array {
        return array_filter(array_map(
            fn(string $urlKey) => $this->category_module->categories[$urlKey],
            $this->categories
        ));
    }

    protected function html(?string $markdown): ?string {
        if (!$markdown) {
            return null;
        }

        $markdown = preg_replace_callback(static::LINK_PATTERN_1, function($match) {
            return ($url = $this->resolveRelativeUrl($match['url']))
                ? "[{$match['title']}]({$url})"
                : $match[0];
        }, $markdown);

        $markdown = preg_replace_callback(static::LINK_PATTERN_2, function($match) {
            return ($url = $this->resolveRelativeUrl($match['url']))
                ? "<{$url}>"
                : $match[0];
        }, $markdown);

        return parent::html($markdown);
    }

    protected function resolveRelativeUrl(string $path): ?string {
        $absolutePath = realpath(dirname($this->absolute_path) . '/' . $path);

        return $absolutePath
            ? Post::new([
                'path' => mb_substr($absolutePath, mb_strlen("{$this->root_path}/")),
            ])->url
            : null;
    }
}