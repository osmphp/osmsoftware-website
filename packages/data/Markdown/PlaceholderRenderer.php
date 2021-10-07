<?php

namespace Osm\Data\Markdown;

use Osm\Core\App;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Core\Object_;
use Osm\Core\Attributes\Serialized;
use Osm\Data\Markdown\Attributes\In_;
use Osm\Framework\Cache\Descendants;

/**
 * @property string $class_name #[Serialized]
 * @property Placeholder[] $placeholders #[Serialized]
 * @property Descendants $descendants
 */
class PlaceholderRenderer extends Object_
{
    const PLACEHOLDER_PATTERN = "/{{\s*(?<placeholder>[^ }]+)\s*}}/u";

    protected function get_placeholders(): array {
        global $osm_app; /* @var App $osm_app */

        $placeholders = [];

        $classNames = $this->descendants->byName(Placeholder::class);

        foreach ($classNames as $name => $className) {
            $class = $osm_app->classes[$className];

            /* @var In_ $in */
            if (!($in = $class->attributes[In_::class] ?? null)) {
                continue;
            }

            if (is_a($this->class_name, $in->class_name, true)) {
                $new = "{$className}::new";
                $placeholders[$name] = $new([
                    'name' => $name,
                ]);
            }
        }

        return $placeholders;
    }

    protected function get_descendants(): Descendants {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->descendants;
    }

    public function render(File $file, string $markdown): string {
        return preg_replace_callback(static::PLACEHOLDER_PATTERN,
            fn(array $match) => $this->renderPlaceholder($markdown, $match, $file),
            $markdown, flags: PREG_OFFSET_CAPTURE);
    }

    protected function renderPlaceholder(string $markdown, array $match,
        File $file): string
    {
        // don't render unknown placeholders
        if (!($placeholder = $this->placeholders[$match['placeholder'][0]] ?? null)) {
            return $match[0][0];
        }

        // don't render the placeholder if it requires starting on a new line,
        // but it isn't
        if ($placeholder->starts_on_new_line &&
            $match[0][1] !== 0 &&
            mb_strpos("\r\n", mb_substr($markdown, $match[0][1] - 1, 1))
                === false)
        {
            return $match[0][0];
        }

        return $placeholder->render($file) ?? $match[0][0];
    }
}