<?php

namespace Osm\Docs\Docs\Routes\Front;

use Osm\Core\App;
use Osm\Core\BaseModule;
use Osm\Docs\Docs\Book;
use Osm\Docs\Docs\Module;
use Osm\Framework\Http\Route;

/**
 * @property string $book_name
 * @property Book $book
 * @property Module $module
 * @property Book[] $books
 */
class BookRoute extends Route
{
    protected function get_book(): Book {
        return $this->books[$this->book_name];
    }

    protected function get_module(): BaseModule {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->modules[Module::class];
    }

    protected function get_books(): array {
        return $this->module->books;
    }
}