# Installation

Install PHP 8, MySql, Node, Gulp and ElasticSearch. Clone the project and edit project settings. Finally, run few commands in the command line.

Contents:

{{ toc }}

## meta.list_text

Install PHP 8, MySql, Node, Gulp and ElasticSearch. Clone the project. Edit
project settings. Finally, run few commands in the command line.

## Prerequisites

Before you begin, install the following prerequisites:

* [PHP 8 or later](https://www.php.net/manual/en/install.php), and enable `curl`, `fileinfo`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `pdo_sqlite`, `sqlite3`
  extensions
* [MySql 8.0 or later](https://dev.mysql.com/downloads/)
* [Node.js, the latest LTS version](https://nodejs.org/en/download/current/)
* [Gulp 4 command line utility](https://gulpjs.com/docs/en/getting-started/quick-start#install-the-gulp-command-line-utility)
* [ElasticSearch 7.14 or later](https://www.elastic.co/downloads/elasticsearch)
* [Osm Framework command line aliases](10-framework-command-line-aliases.md)

## Clone The Project And Configure Its Settings

1. Clone the project into `osmsoftware` directory by running the following command:

        git clone https://github.com/osmphp/osmsoftware-website.git osmsoftware

2. In MySql, create `osmsoftware` database.

3. Create the `.env.Osm_App` file in the project directory, and configure MySql user name and password:

        NAME=osmsoftware
        #PRODUCTION=true
        
        MYSQL_DATABASE="${NAME}"
        MYSQL_USERNAME=...
        MYSQL_PASSWORD=...
        
        SEARCH_INDEX_PREFIX="${NAME}_"


## Install The Project

Run the following commands in the project directory:

    # install dependencies
    composer install

    # make `temp` directory writable
    find temp -type d -exec chmod 777 {} \;
    find temp -type f -exec chmod 666 {} \;

    # compile the tools application
    osmc Osm_Tools

    # collect JS dependencies from all installed modules
    osmt config:npm
        
    # install JS dependencies
    npm install
    
    # build JS, CSS and other assets
    gulp

    # create tables in the MySql database
    osm migrate:up

    # fill in the MySql database and ElasticSearch index with the website data
    osm index

**Note**. Some commands may show no output. Don't worry - it means they worked as expected :)

After creating a project, check that it works in the command line:

    osm

## Using PHP Web Server

The easiest way to try out the application is to use the Web server that is bundled with PHP.

Start the native PHP Web Server in the project directory:

    # start the Web application on the `8000` port
    php -S 0.0.0.0:8000 -t public/Osm_App public/Osm_App/router.php

While the Web server is running, open the application home page in a browser: <http://127.0.0.1:8000/>.

## Using `gulp watch`

That's all - you can begin tinkering project files!

However, before you do, run the following command in the project directory:

    gulp watch

Keep this command running as long as you change the project files. It detects file changes, and automatically:

* recompiles the application,
* rebuilds JS, CSS and other assets.

In some cases, you may need to restart this command.

## Reindexing Website Data

Whenever you change contents of `data/` directory, update MySql and ElasticSearch by running

    osm index