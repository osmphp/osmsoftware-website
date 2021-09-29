<?php

namespace Osm\Docs\Docs;

use Osm\App\App;
use Osm\Core\BaseModule;
use Osm\Framework\Cache\Attributes\Cached;
use Osm\Framework\Settings\Hints\Settings;

/**
 * @property Book[] $books #[Cached('docs_books', callback: 'prepareBooks')]
 *
 * @property Settings $settings
 */
class Module extends BaseModule
{
    public static ?string $app_class_name = App::class;

    public static array $requires = [
        \Osm\Framework\Migrations\Module::class,
        \Osm\Data\Indexing\Module::class,
        \Osm\Data\Markdown\Module::class,
    ];

    public static array $traits = [
        Settings::class => Traits\SettingsTrait::class,
    ];

    protected function get_books(): array {
        $books = [];

        foreach ($this->settings->docs?->books ?? [] as $name => $bookSettings) {
            $books[$name] = $this->createBook($name, $bookSettings);
        }

        $this->books = $books;
        $this->prepareBooks();

        return $books;
    }

    protected function get_settings(): \stdClass|Settings {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->settings;
    }

    protected function createBook(string $name,
        \stdClass|Hints\Settings\Book $bookSettings): Book
    {
        $data = array_merge(['name' => $name], (array)$bookSettings);
        $data['versions'] = $this->createVersions($data['versions'] ?? []);
        return Book::new($data);
    }

    protected function createVersions(array $versionsSettings): array {
        $versions = [];

        foreach ($versionsSettings as $name => $versionSettings) {
            $versions[$name] = $this->createVersion($name, $versionSettings);
        }

        return $versions;
    }

    protected function createVersion(string $name,
        \stdClass|Hints\Settings\Version $versionSettings): Version
    {
        return Version::new(array_merge([
            'name' => $name,
        ], (array)$versionSettings));
    }

    protected function prepareBooks(): void {
        foreach ($this->books as $book) {
            foreach ($book->versions as $version) {
                $version->book = $book;
            }
        }
    }

}