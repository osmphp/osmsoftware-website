# Recreating Search Index. Making Test Suites Work

Yesterday:

* Osm Admin in-browser functionality was fully restored after creating search index in the indexer.
* All test suites started passing again, and being run automatically on `git push`.

Some details: 

{{ toc }}

### meta.abstract

Yesterday:

* Osm Admin in-browser functionality was fully restored after creating search index in the indexer.
* All test suites started passing again, and being run automatically on `git push`.

## Recreating Search Index

[A while ago](../04/21-data-search-index-considerations-better-diff-syntax.md#if-migration-fails-index-must-be-rebuilt), I've decided to move the search index creation from migrations to indexing:

> 1. Search index creation should be a part of indexing. During full reindex,  the indexer should create the index and fill it with data. During partial reindex, it should only update its data.
> 2. Migration should not care about search indexes at all.
> 3. Application logic should check if a search index requires full reindex, and if so, it should not hit ElasticSearch at all. Instead, it should provide alternative query processing by DB means only.

The second point is already implemented, the third one is not relevant just yet.

Let's implement the first point.

    // Osm\Admin\Schema\Indexer\Search
    protected function fullReindex(): void {
        if ($this->search->exists($this->table->table_name)) {
            $this->search->drop($this->table->table_name);
        }

        $this->search->create($this->table->table_name, function(Blueprint $index) {
            foreach ($this->table->properties as $property) {
                if ($property->name === 'id') {
                    continue;
                }

                if ($property->index) {
                    $field = $property->createIndex($index);

                    if ($property->index_filterable) {
                        $field->filterable();
                    }

                    if ($property->index_sortable) {
                        $field->sortable();
                    }

                    if ($property->index_searchable) {
                        $field->searchable();
                    }

                    if ($property->index_faceted) {
                        $field->faceted();
                    }
                }
            }
        });
        ...
    }

## Reviewing Test Suites

### Making Tests Pass

From now on, all tests should pass on the main branch, which currently is `v0.2`.

DONE.

### Running Tests On GitHub

I've also created a GitHub action that runs all the test suites automatically, on Git push:

    # .github/workflows/test.yml
    name: tests
    on:
        push:
        pull_request:
        schedule:
            -   cron: '0 0 * * *'
    
    jobs:
        ubuntu:
            runs-on: ubuntu-latest
    
            name: ubuntu-latest, PHP 8.1, MySql, ElasticSearch
            env:
                NAME: "admin_${{ github.run_number }}"
                MYSQL_USERNAME: root
                MYSQL_PASSWORD: root
    
            steps:
                -   name: Checkout code
                    uses: actions/checkout@v2
    
                -   name: Configure sysctl limits for ElasticSearch
                    run: |
                        sudo swapoff -a
                        sudo sysctl -w vm.swappiness=1
                        sudo sysctl -w fs.file-max=262144
                        sudo sysctl -w vm.max_map_count=262144
    
                -   name: Install ElasticSearch
                    uses: getong/elasticsearch-action@v1.2
                    with:
                        elasticsearch version: '7.6.1'
                        host port: 9200
                        container port: 9200
                        host node port: 9300
                        node port: 9300
                        discovery type: 'single-node'
    
                -   name: Start MySql and create the database
                    run: |
                        sudo systemctl start mysql.service
                        mysql -u root -proot -e "CREATE DATABASE ${{ env.NAME }};"
    
                -   name: Setup PHP
                    uses: shivammathur/setup-php@v2
                    with:
                        php-version: 8.1
                        extensions: mbstring, pdo, sqlite, pdo_sqlite
                        ini-values: variables_order=EGPCS
                        tools: composer:v2
                        coverage: none
                    env:
                        COMPOSER_TOKEN: ${{ secrets.GITHUB_TOKEN }}
    
                -   name: Setup problem matchers
                    run: echo "::add-matcher::${{ runner.tool_cache }}/phpunit.json"
    
                -   name: Install dependencies
                    run: composer update --prefer-dist --no-interaction --no-progress
    
                -   name: Compile applications
                    run: |
                        php vendor/osmphp/core/bin/compile.php Osm_Admin_Samples
                        php vendor/osmphp/core/bin/compile.php Osm_Tools
                        php vendor/osmphp/core/bin/compile.php Osm_Project
    
                -   name: Collect JS dependencies
                    run: php vendor/osmphp/framework/bin/tools.php config:npm
    
                -   name: Install Node modules
                    run: npm install
    
                -   name: Run Gulp
                    run: gulp
    
                -   name: Create System DB Tables
                    run: php bin/run.php migrate:up
                    env:
                        MYSQL_PORT: ${{ job.services.mysql.ports[3306] }}
                        MYSQL_DATABASE: ${{ env.NAME }}
                        SEARCH_INDEX_PREFIX: "${{ env.NAME }}_"
    
                -   name: Execute migration tests
                    run: vendor/bin/phpunit --configuration phpunit_migrations.xml
                    env:
                        MYSQL_PORT: ${{ job.services.mysql.ports[3306] }}
                        MYSQL_DATABASE: ${{ env.NAME }}
                        SEARCH_INDEX_PREFIX: "${{ env.NAME }}_"
    
                -   name: Execute query tests
                    run: vendor/bin/phpunit --configuration phpunit_queries.xml
                    env:
                        MYSQL_PORT: ${{ job.services.mysql.ports[3306] }}
                        MYSQL_DATABASE: ${{ env.NAME }}
                        SEARCH_INDEX_PREFIX: "${{ env.NAME }}_"
    
                -   name: Execute main test suite
                    run: vendor/bin/phpunit
                    env:
                        MYSQL_PORT: ${{ job.services.mysql.ports[3306] }}
                        MYSQL_DATABASE: ${{ env.NAME }}
                        SEARCH_INDEX_PREFIX: "${{ env.NAME }}_"
 