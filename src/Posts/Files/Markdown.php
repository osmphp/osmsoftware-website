<?php

declare(strict_types=1);

namespace My\Posts\Files;

use Osm\Core\App;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Core\Exceptions\NotSupported;
use Osm\Core\Object_;
use function Osm\__;

/**
 * @property string $path
 *
 * @property string $root_path
 * @property string $absolute_path
 * @property bool $exists
 * @property string $original_text
 * @property string[] $original_lines
 * @property ?string $title_line
 * @property ?string $title
 * @property string $text
 */
class Markdown extends Object_
{
    // single line patterns
    const H1_PATTERN = "/^#\\s*(?<title>[^#{]+)/u";
    const HEADER_PATTERN = "/^(?<depth>#+)\\s*(?<title>[^#{\\r\\n]+)#*[ \\t]*(?:{(?<attributes>[^}\\r\\n]*)})?\\r?$/mu";
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
        $this->extractTitle();
        return $this->title;
    }

    protected function get_title_line(): string {
        $this->extractTitle();
        return $this->title_line;
    }

    protected function extractTitle(): void {
        foreach ($this->original_lines as $no => $line) {
            if (preg_match(static::H1_PATTERN, $line, $match)) {
                $this->title = trim($match['title']);
                $this->title_line = $line;
                return;
            }
        }

        $this->title = null;
        $this->title_line = null;
    }

    protected function get_text(): string {
        $text = $this->original_text;

        // remove title
        $text = $this->removeTitle($text);

        return $text;
    }

    protected function removeTitle(string $text): string {
        throw new NotImplemented($this);
    }

}