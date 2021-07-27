<?php

declare(strict_types=1);

namespace My\Posts;

use Carbon\Carbon;
use Michelf\MarkdownExtra;
use My\Categories\Category;
use My\Categories\Module as CategoryModule;
use My\Markdown\File;
use My\Markdown\Exceptions\InvalidPath;
use Osm\Core\App;
use Osm\Core\BaseModule;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Framework\Http\Http;
use Symfony\Component\DomCrawler\Crawler;
use function Osm\__;

/**
 * @property ?string $list_text
 * @property ?string $list_html
 * @property Carbon $created_at
 * @property string $url_key
 * @property string $url
 * @property Http $http
 * @property ?string $main_category
 * @property string[] $additional_categories
 * @property string[] $categories
 * @property ?Category $main_category_file
 * @property Category[] $category_files
 * @property Category[] $additional_category_files
 * @property CategoryModule $category_module
 * @property string[] $broken_links
 * @property string[] $external_broken_links
 * @property ?string $meta_description
 */
class Post extends File
{
    const PATH_PATTERN = '|^(?<year>[0-9]{2})/(?<month>[0-9]{2})/(?<day>[0-9]{2})-(?<url_key>.*)\.md$|u';

    // [title](url), but not ![title](url)
    const LINK_PATTERN_1 = '/(?<!\!)\[(?<title>[^]]*)\]\((?<url>[^)]*)\)/u';

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

    protected function get_main_category(): ?string {
        foreach ($this->category_module->categories as $category) {
            if (str_starts_with($this->url_key, $category->url_key . '-')) {
                return $category->url_key;
            }
        }

        return null;
    }

    protected function get_additional_categories(): array {
        return array_filter(array_map(fn(string $urlKey) =>
            isset($this->category_module->categories[$urlKey])
                ? $urlKey
                : null
        , $this->meta->categories ?? []));
    }

    protected function get_categories(): array {
        $categories = $this->additional_categories;

        if ($this->main_category) {
            array_unshift($categories, $this->main_category);
        }

        return array_unique($categories);
    }

    protected function get_category_module(): CategoryModule|BaseModule {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->modules[CategoryModule::class];
    }

    protected function get_main_category_file(): ?Category {
        return $this->main_category
            ? $this->category_module->categories[$this->main_category]
            :null;
    }

    protected function get_category_files(): array {
        return array_filter(array_map(
            fn(string $urlKey) => $this->category_module->categories[$urlKey],
            $this->categories
        ));
    }

    protected function get_additional_category_files(): array {
        return array_filter(array_map(
            fn(string $urlKey) => $this->category_module->categories[$urlKey],
            $this->additional_categories
        ));
    }

    protected function html(?string $markdown): ?string {
        if (!$markdown) {
            return null;
        }

        $markdown = $this->transformRelativeLinks($markdown);
        $markdown = $this->transformTags($markdown);

        return parent::html($markdown);
    }

    protected function resolveRelativeUrl(string $path): ?string {
        $path = $this->removeHashTag($path, $hashTag);

        $absolutePath = realpath(dirname($this->absolute_path) . '/' . $path);

        return $path && $absolutePath
            ? Post::new([
                'path' => mb_substr($absolutePath, mb_strlen("{$this->root_path}/")),
            ])->url . $hashTag
            : null;
    }

    protected function transformRelativeLinks(string $markdown): string {
        $markdown = preg_replace_callback(static::LINK_PATTERN_1, function($match) {
            return ($url = $this->resolveRelativeUrl($match['url']))
                ? "[{$match['title']}]({$url})"
                : $match[0];
        }, $markdown);

        return preg_replace_callback(static::LINK_PATTERN_2, function($match) {
            return ($url = $this->resolveRelativeUrl($match['url']))
                ? "<{$url}>"
                : $match[0];
        }, $markdown);
    }

    protected function transformTags(string $markdown): string {
        return preg_replace_callback(static::TAG_PATTERN, function($match) {
            return $this->renderTag($match['tag']);
        }, $markdown);
    }

    protected function renderTag(string $tag): string {
        return $tag == 'toc' ? $this->renderToc() : "{{ {$tag} }}";
    }

    protected function renderToc(): string {
        $markdown = '';

        foreach ($this->toc as $urlKey => $tocEntry) {
            $markdown .= str_repeat(' ', ($tocEntry->depth - 2) * 4)
                . "* [" . $tocEntry->title . "](#{$urlKey})\n";
        }
        return "{$markdown}\n";
    }

    protected function get_broken_links(): array {
        $brokenLinks = [];

        $crawler = new Crawler($this->original_html);
        foreach ($crawler->filter('a') as $link) {
            if ($this->isLinkBroken($url = $this->removeHashTag(
                $link->getAttribute('href'))))
            {
                $brokenLinks[] = $url;
            }
        }

        foreach ($crawler->filter('img') as $link) {
            if ($this->isLinkBroken(
                $url = $link->getAttribute('src')))
            {
                $brokenLinks[] = $url;
            }
        }

        return $brokenLinks;
    }

    protected function isLinkBroken(string $path): bool {
        if (!$path) {
            // ignore empty URLs
            return false;
        }

        if (str_starts_with($path, 'http://') ||
            str_starts_with($path, 'https://') ||
            str_starts_with($path, '/'))
        {
            // ignore absolute URLs
            return false;
        }

        $absolutePath = realpath(dirname($this->absolute_path) . '/' . $path);

        return $absolutePath === false;
    }

    protected function get_external_broken_links(): array {
        $brokenLinks = [];

        $crawler = new Crawler($this->original_html);
        foreach ($crawler->filter('a') as $link) {
            if ($this->isExternalLinkBroken($url = $this->removeHashTag(
                $link->getAttribute('href'))))
            {
                $brokenLinks[] = $url;
            }
        }

        foreach ($crawler->filter('img') as $link) {
            if ($this->isExternalLinkBroken(
                $url = $link->getAttribute('src')))
            {
                $brokenLinks[] = $url;
            }
        }

        return $brokenLinks;
    }

    protected function isExternalLinkBroken(string $url): bool {
        if (!$url) {
            // ignore empty URLs
            return false;
        }

        if (!(str_starts_with($url, 'http://') ||
            str_starts_with($url, 'https://')))
        {
            // ignore relative URLs
            return false;
        }

        if (!$this->externalUrlExists($url)) {
            return true;
        }

        // GitHub doesn't return 404 for missing files, so check raw file
        // version
        if (preg_match(
            '|https://github.com/(?<user>[^/]+)/(?<repo>[^/]+)/blob/(?<path>.+)|',
            $url, $match))
        {
            return !$this->externalUrlExists(
                "https://raw.githubusercontent.com/" .
                "{$match['user']}/{$match['repo']}/{$match['path']}");

        }

        return false;
    }

    protected function externalUrlExists(string $url): bool {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        $returnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $returnCode == 200;
    }

    protected function removeHashTag(?string $url, ?string &$hashTag = null)
        : ?string
    {
        if (!$url) {
            return $url;
        }

        if (($pos = mb_strpos($url, '#')) !== false) {
            $hashTag = mb_substr($url, $pos);
            $url = mb_substr($url, 0, $pos);
        }

        return $url;
    }

    protected function get_meta_description(): string {
        return $this->meta?->description ?? $this->list_text;
    }
}