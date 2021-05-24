<?php

declare(strict_types=1);

namespace My\Posts;

use Carbon\Carbon;
use My\Posts\Exceptions\InvalidJson;
use My\Posts\Exceptions\InvalidPath;
use My\Posts\Exceptions\TooManyDuplicateHeadings;
use Osm\Core\App;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Core\Exceptions\NotSupported;
use Osm\Core\Object_;
use function Osm\__;
use function Osm\merge;

/**
 * @property string $path
 *
 * @property string $root_path
 * @property string $absolute_path
 * @property bool $exists
 * @property ?\stdClass $model
 * @property Carbon $created_at
 * @property string $url_key
 * @property string $original_text
 * @property string[] $original_lines
 * @property ?string $title
 * @property \stdClass $toc
 * @property \stdClass $meta
 * @property string $text
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

    protected function get_model(): ?\stdClass {
        return $this->exists
            ? merge((object)[
                'title' => $this->title,
                'created_at' => $this->created_at,
                'url_key' => $this->url_key,
            ], $this->meta)
            : null;
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
                        "Invalid JSON in 'meta' section"));
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
}