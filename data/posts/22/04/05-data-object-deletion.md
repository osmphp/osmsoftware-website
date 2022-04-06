# Object Deletion

I implemented the last user action that is typical to a CRUD application - object deletion.

Here is how it went:

{{ toc }}

### meta.abstract

I implemented the last user action that is typical to a CRUD application - object deletion.

## HTTP Error 500 

I opened Osm Admin project today - and nothing works. All I see is 500 server error. Without any details.

What's going on?

Here is what the Nginx error log file in `/var/log/nginx` says:

    PHP Parse error:  Unclosed '{' on line 611 does not match ')' 
        in /home/vo/projects/admin2/src/Queries/Query.php on line 616
        
One extra `)`. Happens.

## `DELETE /`

The last user action is object deletion:

    // Osm\Admin\Tables\Routes\Admin\Delete
    #[Ui(Admin::class), Name('DELETE /')]
    class Delete extends Route
    {
        public function run(): Response
        {
            $query = ui_query($this->table->name);
    
            $query
                ->fromUrl($this->http->query,
                    'limit', 'offset', 'order', 'select')
                ->delete();
    
            return json_response((object)[
                'url' => $this->table->url('GET /'),
            ]);
        }
    }
    
    // Osm\Admin\Ui\Query
    public function delete(): void {
        foreach ($this->filters as $filter) {
            $filter->queryDb($this->db_query);
        }

        $this->db_query->delete();
    }

## Delete Notifications Should Not Be Cascade-Deleted    

After deleting products, I noticed that product count and the faceted navigation counts are not affected. The only explanation is that the deleted products are not deleted from the search  index.

I checked the `zi9__products__deletes` notification table - it's empty. Ah, they may be deleted with `ON DELETE CASCADE` rule. Let's change it and [re-create the database](../03/09-data-returning-to-building-in-public-facets-search-indexing.md#database):

    // Osm\Admin\Schema\Indexer
    public function createNotificationTables(Table $source): void  {
        if ($deleted = $listensTo[Query::DELETED] ?? null) {
            $this->createNotificationTable($source, $deleted, cascade: false);
        }
    }

## Delete Notification Should Be Created *Before* DELETE

`CASCADE DELETE` rule is gone, and yet the result is the same.

The problem was that I was trying to create a notification after the object is deleted. It's too late to fetch the deleted IDs by then, let's move the notification creation before that:

    public function delete(): void {
        $this->db->transaction(function() {
            // create notification records for the dependent objects in
            // other tables, and for search index entries
            $this->notifyListeners(static::DELETED);

            // generate and execute SQL DELETE statement
            $bindings = [];
            $sql = $this->generateDelete($bindings);
            $this->db->connection->delete($sql, $bindings);

            ...
        });
    }

## Dealing With Async Nature Of Search Index

Everything kind of work, and yet there is one issue left. Just after deleting an object, product count and faceted navigation still doesn't change, but if you refresh the page once again - it does.

**Later**. The reason is that the page is refreshed *before* search index is updated. And to solve this issue, I need to wait for product search indexing to happen - and only then refresh the page.

    