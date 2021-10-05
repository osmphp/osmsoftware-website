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
            fn(array $match) => $this->renderPlaceholder($match['placeholder'], $file)
                ?? $match[0],
            $markdown);
    }

    protected function renderPlaceholder(string $placeholder, File $file)
        : ?string
    {
        return $this->placeholders[$placeholder]?->render($file);
    }
}