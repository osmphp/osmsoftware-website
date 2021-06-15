# Indexing for search

This is the 6-th blog post in the "Building `osmcommerce.com`" series. This post covers creating the full-text search index, and filling it in with data.

{{ toc }}

## meta

    {
        "series": "Building osmcommerce.com", 
        "series_part": 6
    }

## Migration

Create a migration file `src/Posts/Migrations/M02_posts__search.php`, and define an index and its fields:

    ...
    public function create(): void {
        if ($this->search->exists('posts')) {
            $this->search->drop('posts');
        }

        $this->search->create('posts', function (Blueprint $index) {
            $index->string('title')
                ->searchable();
            $index->string('text')
                ->searchable();
            $index->string('tags')
                ->array()
                ->searchable()
                ->filterable();
            $index->string('series')
                ->array()
                ->searchable()
                ->filterable();
            $index->string('created_at')
                ->sortable();
            $index->int('year')
                ->filterable();
            $index->int('month')
                ->filterable();
        });
    }
    ...

## Add extension points in DB indexing code

In the `Indexer` class:

    ...
    protected function indexFile(string $path): void {
        ...
        $this->afterSaved($id, $parser);
    }
    ...
    protected function markDeletedFiles() {
        ...
        foreach ($query->get(['id', 'path']) as $item) {
            $absolutePath = "{$this->root_path}/{$item->path}";
            if (!is_file($absolutePath)) {
                ...
                $this->afterDeleted($item->id);
            }
        }
    }

## Fill in the extension points with the search indexing

In the `Indexer` class:

    protected function afterSaved(int $id, MarkdownParser $parser): void {
        $data = [
            'title' => $parser->title,
            'text' => $parser->text,
            'tags' => $parser->meta->tags ?? [],
            'series' => isset($parser->meta->series)
                ? array_keys((array)$parser->meta->series)
                : [],
            'year' => $parser->created_at->year,
            'month' => $parser->created_at->month,
            'created_at' => $parser->created_at->format("Y-m-d\TH:i:s")
        ];

        if ($this->existsInSearch($id)) {
            $this->search->index('posts')
                ->update($id, $data);
        }
        else {
            $this->search->index('posts')
                ->insert(array_merge(['id' => $id], $data));
        }
    }

    protected function afterDeleted(int $id): void {
        if ($this->existsInSearch($id)) {
            $this->search->index('posts')
                ->delete($id);
        }
    }

    protected function existsInSearch(int $id): bool {
        return $this->search->index('posts')
            ->where('id', '=', $id)->id() !== null;
    }

## Add assertions about search index to unit tests

For example:

    public function test_db_indexing_one_file() {
        // GIVEN the sample posts

        // WHEN you index the `welcome.md`
        Indexer::new(['path' => '21/05/18-welcome.md'])->run();

        // THEN it's in the database
        $this->assertNotNull($id = $this->db->table('posts')
            ->where('path', '21/05/18-welcome.md')
            ->value('id')
        );

        // AND in search
        $this->assertNotNull($this->app->search->index('posts')
            ->where('id', '=', $id)
            ->id()
        );
    }
