# Clearing Search Index

Earlier this month, Osm Framework [started requiring 'limit()' on all search index queries](12-framework-search-hit-limit-must-be-explicit.md), and the naive Osm Admin search indexer code that deletes all entries one by one doesn't do that:

    // Osm\Admin\Schema\Indexer\Search::fullReindex()
    foreach ($this->searchQuery()->ids() as $id) {
        $this->searchQuery()->delete($id);
    }

It's time to create a `deleteAll()` method for this use case:

    // Osm\Framework\Search\Query
    public function deleteAll(): void {
        throw new NotImplemented($this);
    }

    // Osm\Framework\ElasticSearch\Query
    public function deleteAll(): void {
        $request = $this->fireFunction('elastic:deleting_all', [
            'index' => "{$this->search->index_prefix}{$this->index_name}",
            'refresh' => $this->search->refresh,
            'body' => [
                'query' => [
                    'match_all' => (object)[],
                ],
            ],
        ]);

        $this->search->client->deleteByQuery($request);

        $this->fire('elastic:deleted_all');
    }

    // Osm\Framework\AlgoliaSearch\Query
    public function deleteAll(): void {
        $this->fire('algolia:deleting_all');

        $request = $this->search->initIndex($this->index_name)
            ->deleteBy(['filters' => 'id > 0']);

        if ($this->search->wait) {
            $request->wait();
        }

        $this->fire('algolia:deleted_all');
    }

This method also gets called in the test suite. 

### meta.abstract

Use `deleteAll()` method on a search query to, well, delete all entries from a search index.   