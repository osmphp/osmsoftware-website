<?php

declare(strict_types=1);

namespace My\Posts;

use Carbon\Carbon;
use Michelf\MarkdownExtra;
use My\Posts\Exceptions\InvalidJson;
use My\Posts\Exceptions\InvalidPath;
use My\Posts\Exceptions\TooManyDuplicateHeadings;
use My\Posts\Hints\Series;
use My\Posts\Hints\Tag;
use Osm\Core\App;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Core\Exceptions\NotSupported;
use Osm\Core\Object_;
use Osm\Framework\Http\Http;
use function Osm\__;
use function Osm\merge;

/**
 * @property string $path
 *
 * @property string $root_path
 * @property string $absolute_path
 * @property bool $exists
 * @property Carbon $modified_at
 * @property Tag[]|null $tags
 * @property ?Series $series
 * @property ?string $list_text
 * @property ?string $list_html
 * @property Carbon $created_at
 * @property string $url_key
 * @property string $original_text
 * @property string[] $original_lines
 * @property ?string $title
 * @property \stdClass $toc
 * @property \stdClass $meta
 * @property string $text
 * @property string $url
 * @property Http $http
 * @property string $html
 */
class MarkdownParser extends Object_
{
    const MAX_DUPLICATE_HEADINGS = 100;

    // Regex patterns
    const PATH_PATTERN = '|^(?<year>[0-9]{2})/(?<month>[0-9]{2})/(?<day>[0-9]{2})-(?<url_key>.*)\.md$|u';
    const H1_PATTERN = '/^#\s*(?<title>[^#{\r\n]+)\s*/mu';
    const SECTION_PATTERN = '/^(?<depth>#+)\s*(?<title>[^#{\r\n]+)#*[ \t]*(?:{(?<attributes>[^}\r\n]*)})?\r?$\s*(?<text>[\s\S]*?)(?=^#|\Z)/mu';

    // obsolete patterns
    const HEADER_PATTERN = '/^(?<depth>#+)\s*(?<title>[^#{\r\n]+)#*[ \t]*(?:{(?<attributes>[^}\r\n]*)})?\r?$/mu';
    const IMAGE_LINK_PATTERN = "/!\\[(?<description>[^\\]]*)\\]\\((?<url>[^\\)]+)\\)/u";
    const TAG_PATTERN = "/(?<whitespace> {4})?(?<opening_backtick>`)?{{\\s*(?<tag>[^ }]*)(?<args>.*)}}(?<closing_backtick>`)?/u";
    const ARG_PATTERN = "/(?<key>[a-z0-9_]+)\\s*=\\s*\"(?<value>[^\"]*)\"/u";
    const ID_PATTERN = "/#(?<id>[^ ]+)/u";
    const LINK_PATTERN = "/\\[(?<title>[^\\]]+)\\]\\((?<url>[^\\)]+)\\)/u";

    // multi-line patterns
    const ALTERNATE_HEADER_PATTERN = "/\\n(?<title>[^{\\r\\n]+)(?:{(?<attributes>[^}\\r\\n]*)})?\\r?\\n--/mu";

    protected function get_root_path(): string {
        global $osm_app; /* @var App $osm_app */

        return "{$osm_app->paths->data}/posts";
    }

    protected function get_absolute_path(): string {
        return "{$this->root_path}/{$this->path}";
    }

    protected function get_exists(): bool {
        return file_exists($this->absolute_path);
    }

    protected function get_created_at(): Carbon {
        $this->parsePath();
        return $this->created_at;
    }

    protected function get_original_text(): string {
        $this->assumeExists();
        return file_get_contents($this->absolute_path);
    }

    protected function assumeExists() {
        if (!$this->exists) {
            throw new NotSupported(__(
                "Before processing ':file', check that it exists using the 'exists' property",
                ['file' => $this->absolute_path],
            ));
        }
    }

    protected function get_original_lines(): array {
        return array_map('rtrim',
            explode("\n", $this->original_text));
    }

    protected function get_title(): string {
        $this->parseText();
        return $this->title;
    }

    protected function get_text(): string {
        $this->parseText();
        return $this->text;
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

    protected function get_meta(): \stdClass {
        $this->parseText();
        return $this->meta;
    }

    protected function get_toc(): \stdClass {
        $this->parseText();
        return $this->toc;
    }

    protected function parseText(): void {
        $text = $this->original_text;

        $text = $this->parseTitle($text);
        $text = $this->parseSections($text);

        $this->text = $text;
    }

    protected function parseTitle(string $text): string {
        $this->title = '';

        return preg_replace_callback(static::H1_PATTERN, function($match) {
            $this->title = $match['title'];

            return '';
        }, $text);
    }

    protected function parseSections(string $text): string {
        $this->meta = new \stdClass();
        $this->toc = new \stdClass();

        return preg_replace_callback(static::SECTION_PATTERN, function($match) {
            if ($match['title'] === 'meta') {
                if (($json = json_decode($match['text'])) === null) {
                    throw new InvalidJson(__(
                        "Invalid JSON in 'meta' section of ':file' file",
                        ['file' => $this->path]));
                }
                $this->meta = merge($this->meta, $json);

                return '';
            }

            if (str_starts_with($match['title'], 'meta.')) {
                $property = substr($match['title'], strlen('meta.'));
                $this->meta->$property = $match['text'];

                return '';
            }

            $id = $this->generateUniqueId($match['title']);

            $this->toc->$id = (object)[
                'depth' => mb_strlen($match['depth']),
                'title' => $match['title'],
            ];

            // by default, keep the section in the text
            return $match[0];
        }, $text);
    }

    protected function generateUniqueId(string $heading): string {
        $id = $this->generateId($heading);

        for ($i = 0; $i < static::MAX_DUPLICATE_HEADINGS; $i++) {
            $suffixedId = $i === 0
                ? $id
                : "{$id}-$i";

            if (!isset($this->toc->$suffixedId)) {
                return $suffixedId;
            }
        }

        throw new TooManyDuplicateHeadings(__("Too many ':heading' headings",
            ['heading' => $heading]));
    }

    protected function generateId(string $heading): string {
        $id = mb_strtolower($heading);

        $id = preg_replace('/[^\w\d\- ]+/u', ' ', $id);
        $id = preg_replace('/\s+/u', '-', $id);
        $id = preg_replace('/\-+$/u', '', $id);

        return $id;
    }

    protected function get_modified_at(): Carbon {
        $this->assumeExists();
        return Carbon::createFromTimestamp(filemtime($this->absolute_path));
    }

    protected function get_url(): string {
        return "{$this->http->base_url}/blog/" .
            "{$this->created_at->format("y/m")}/{$this->url_key}.html";
    }

    protected function get_http(): Http {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->http;
    }

    protected function get_html(): string {
        return $this->html($this->text);
    }

    protected function html(?string $markdown): ?string {
        if (!$markdown) {
            return null;
        }

        $html = MarkdownExtra::defaultTransform($markdown);

        // fix code blocks
        return str_replace("\n</code>", '</code>', $html);
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