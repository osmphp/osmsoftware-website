<?php

namespace Osm\Docs\Docs\Indexers;

use Osm\Core\App;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Data\Indexing\Indexer;
use Osm\Docs\Docs\Book;
use Osm\Docs\Docs\Module;
use Osm\Docs\Docs\Page;
use Osm\Docs\Docs\Sources\DbDocs;
use Osm\Docs\Docs\Sources\SearchDocs;
use Osm\Framework\Db\Db;
use Osm\Framework\Search\Search;

/**
 * @property DbDocs $db__docs
 * @property SearchDocs $target
 *
 * @property Db $db
 * @property Search $search
 * @property Module $module
 * @property Book[] $books
 */

class SearchIndexer extends Indexer
{
    public function run(): void
    {
        if ($this->rebuild()) {
            $this->clear();
        }

        $this->index();
        $this->retire();
    }

    protected function get_db(): Db {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->db;
    }

    protected function get_search(): Search {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->search;
    }

    protected function clear(): void {
        foreach ($this->search->index('docs')->ids() as $id) {
            $this->search->index('docs')->delete($id);
        }
    }

    protected function index(): void {
        $query = $this->db->table('docs');

        if (!$this->db__docs->rebuild) {
            $query->whereIn('id', array_keys($this->db__docs->saved_ids));
        }

        foreach ($query->get(Page::KEY_DB_COLUMNS) as $item) {
            if ($page = Page::fromDb($item)) {
                $this->indexPage($item->id, $page);
            }
        }
    }

    protected function indexPage(int $id, Page $page): void {
        if ($this->exists($id)) {
            $this->update($id, $page);
        }
        else {
            $this->insert($id, $page);
        }
    }

    protected function exists(int $id): bool {
        return $this->search->index('docs')
            ->where('id', '=', $id)->id() !== null;
    }

    protected function update(int $id, Page $page): void {
        $this->search->index('docs')
            ->update($id, $this->data($page));

        $this->idSaved($id);
    }

    protected function insert(int $id, Page $page): void {
        $this->search->index('docs')
            ->insert(array_merge(['id' => $id], $this->data($page)));

        $this->idSaved($id);
    }

    protected function data(Page $page): array {
        return [
            'title' => $page->title,
            'text' => $page->text,
        ];
    }

    protected function retire(): void {
        foreach (array_keys($this->db__docs->deleted_ids) as $id) {
            $this->retirePage($id);
        }
    }

    protected function retirePage(int $id): void {
        if ($this->exists($id)) {
            $this->delete($id);
        }

        $this->idDeleted($id);
    }

    protected function delete(int $id): void {
        $this->search->index('docs')
            ->delete($id);
    }

}