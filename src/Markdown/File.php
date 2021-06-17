<?php

declare(strict_types=1);

namespace My\Markdown;

use Carbon\Carbon;
use Michelf\MarkdownExtra;
use My\Markdown\Exceptions\InvalidJson;
use My\Markdown\Exceptions\TooManyDuplicateHeadings;
use Osm\Core\Exceptions\NotSupported;
use Osm\Core\Object_;
use function Osm\__;
use function Osm\merge;
use Osm\Core\Attributes\Serialized;

/**
 * @property string $path #[Serialized]
 *
 * @property string $root_path #[Serialized] Define getter in derived classes
 * @property string $absolute_path #[Serialized]
 * @property bool $exists #[Serialized]
 * @property Carbon $modified_at #[Serialized]
 * @property string $original_text #[Serialized]
 * @property ?string $title #[Serialized]
 * @property ?string $title_html #[Serialized]
 * @property \stdClass $toc #[Serialized]
 * @property \stdClass $meta #[Serialized]
 * @property string $text #[Serialized]
 * @property string $html #[Serialized]
 */
class File extends Object_
{
    const MAX_DUPLICATE_HEADINGS = 100;

    // Regex patterns
    const H1_PATTERN = '/^#\s*(?<title>[^#{\r\n]+)\s*/mu';
    const SECTION_PATTERN = '/^(?<depth>#+)\s*(?<title>[^#{\r\n]+)#*[ \t]*(?:{(?<attributes>[^}\r\n]*)})?\r?$\s*(?<text>[\s\S]*?)(?=^#|\Z)/mu';

    // obsolete patterns
    const HEADER_PATTERN = '/^(?<depth>#+)\s*(?<title>[^#{\r\n]+)#*[ \t]*(?:{(?<attributes>[^}\r\n]*)})?\r?$/mu';
    const IMAGE_LINK_PATTERN = "/!\\[(?<description>[^\\]]*)\\]\\((?<url>[^\\)]+)\\)/u";
    const TAG_PATTERN = "/(?<whitespace> {4})?(?<opening_backtick>`)?{{\\s*(?<tag>[^ }]*)(?<args>.*)}}(?<closing_backtick>`)?/u";

    protected function get_absolute_path(): string {
        return "{$this->root_path}/{$this->path}";
    }

    protected function get_exists(): bool {
        return file_exists($this->absolute_path);
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

    protected function get_title(): string {
        $this->parseText();
        return $this->title;
    }

    protected function get_text(): string {
        $this->parseText();
        return $this->text;
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

    protected function get_html(): ?string {
        return $this->html($this->text);
    }

    protected function get_title_html(): string {
        return $this->inlineHtml($this->title);
    }

    protected function html(?string $markdown): ?string {
        if (!$markdown) {
            return null;
        }

        $html = MarkdownExtra::defaultTransform($markdown);

        // fix code blocks
        return str_replace("\n</code>", '</code>', $html);
    }

    protected function inlineHtml(?string $markdown): ?string {
        if (!$markdown) {
            return null;
        }

        $html = $this->html($markdown);

        return str_replace(['<p>', '</p>'], '', $html);
    }
}