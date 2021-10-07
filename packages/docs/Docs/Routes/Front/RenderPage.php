<?php

namespace Osm\Docs\Docs\Routes\Front;

use Osm\Core\App;
use Osm\Core\Exceptions\NotImplemented;
use Osm\Docs\Docs\Page;
use Osm\Framework\Db\Db;
use Osm\Framework\Http\Exceptions\NotFound;
use Symfony\Component\HttpFoundation\Response;
use function Osm\view_response;

/**
 * @property string $path
 * @property Db $db
 */
class RenderPage extends VersionRoute
{
    public function run(): Response {
        $item = $this->db->table('docs')
            ->where('book', $this->book_name)
            ->where('version', $this->version_name)
            ->where('url', "/{$this->path}")
            ->first(Page::KEY_DB_COLUMNS);

        if ($item) {
            return view_response('docs::pages.page', [
                'page' => Page::fromDb($item),
            ]);
        }

        throw new NotFound();
    }

    protected function get_db(): Db {
        global $osm_app; /* @var App $osm_app */

        return $osm_app->db;
    }
}