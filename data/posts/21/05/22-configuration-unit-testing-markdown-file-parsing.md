# Configuration, unit testing, Markdown file parsing

This is the fourth blog post in the series describing how `osmcommerce.com` was built. This post covers configuration files, unit testing, and Markdown file parsing.

{{ toc }}

## meta

    {
        "series": "Building osmcommerce.com", 
        "series_part": 4
    }

## Main vs sample application

As described in [Applications and modules](/todo), there are several applications in the codebase, each having a different set of modules (different features), different configuration, different data storage.

The main application, named `Osm_App`, is the one that is actually hosted in production. The sample application, `My_Samples`, includes all the modules of the main application plus optional additional modules that are only used in unit tests.

## Environment and configuration files

Application configuration file, `settings.{{ app_name }}.php`, specifies the most basic settings for the application: the database connection settings, the search index connection settings, the locale, and more. Application configuration files are under version control, meaning that they are the same both for local virtual machine, and for remote server.

However, machine-specific details are not hard-coded there. Instead, they are taken from the OS environment variables, and if not specified there, from the `.env.{{ app_name }}` file. The `.env` files are not under version control, so they are different for any target machine. It also means that passwords and other sensitive information stored in the `.env` files won't accidentally end up exposed in the Git repository.

Let's create configuration and environment files both for main and sample applications:

1. Create `settings.php` with configuration by both applications. It basically says: use MySql and ElasticSearch with the connection settings specified in the `.env` file:

        <?php
        
        declare(strict_types=1);
        
        /* @see \Osm\Framework\Settings\Hints\Settings */
        return (object)[
            'db' => [
                'driver' => 'mysql',
                'url' => $_ENV['MYSQL_DATABASE_URL'] ?? null,
                'host' => $_ENV['MYSQL_HOST'] ?? 'localhost',
                'port' => $_ENV['MYSQL_PORT'] ?? '3306',
                'database' => "{$_ENV['MYSQL_DATABASE']}",
                'username' => $_ENV['MYSQL_USERNAME'],
                'password' => $_ENV['MYSQL_PASSWORD'],
                'unix_socket' => $_ENV['MYSQL_SOCKET'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
                'options' => extension_loaded('pdo_mysql') ? array_filter([
                    PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                ]) : [],
            ],
            'search' => [
                'driver' => 'elastic',
                'index_prefix' => $_ENV['SEARCH_INDEX_PREFIX'],
                'hosts' => [
                    $_ENV['ELASTIC_HOST'] ?? 'localhost:9200',
                ],
                'retries' => 2,
            ],
        ];

2. Create `settings.Osm_App.php` configuration file for the main application. It basically just uses the shared configuration:

        <?php
        
        declare(strict_types=1);
        
        /* @see \Osm\Framework\Settings\Hints\Settings */
        return \Osm\merge((object)[
            // app-specific settings
        ], include __DIR__ . '/settings.php');

3. Create `settings.My_Samples.php` configuration file for the sample application. It adds a setting that forces the ElasticSearch to wait until it actually updates the index, and only then return control to the caller:

        <?php
        
        declare(strict_types=1);
        
        /* @see \Osm\Framework\Settings\Hints\Settings */
        return \Osm\merge((object)[
            // app-specific settings
            'search' => [
                'refresh' => true, // index new data immediately
            ],
        ], include __DIR__ . '/settings.php');

4. Create `.env.Osm_App`:

        NAME=osmcommerce
        
        MYSQL_DATABASE="${NAME}"
        MYSQL_USERNAME=...
        MYSQL_PASSWORD=...
        
        SEARCH_INDEX_PREFIX="${NAME}_"

5. Create `.env.My_Samples`:

        NAME=osmcommerce_test
        
        MYSQL_DATABASE="${NAME}"
        MYSQL_USERNAME=...
        MYSQL_PASSWORD=...
        
        SEARCH_INDEX_PREFIX="${NAME}_"

   As you can see, environment variables are also very similar, only that MySql database name and ElasticSearch index prefix are different.

6. Finally, create mentioned databases on the virtual machine:

       mysql -u root -p -e "CREATE DATABASE osmcommerce; GRANT ALL PRIVILEGES ON osmcommerce.* TO 'vagrant'@'localhost'" 
       mysql -u root -p -e "CREATE DATABASE osmcommerce_test; GRANT ALL PRIVILEGES ON osmcommerce_test.* TO 'vagrant'@'localhost'" 

## `My_Posts` module

Let's create one module that will encapsulate all the logic around blog posts. Create `src/Posts/Module.php`:

    <?php
    
    declare(strict_types=1);
    
    namespace My\Posts;
    
    use Osm\App\App;
    use Osm\Core\Attributes\Name;
    use Osm\Core\BaseModule;
    
    #[Name('posts')]
    class Module extends BaseModule
    {
        public static ?string $app_class_name = App::class;
    
        public static array $requires = [
            \My\Base\Module::class,
        ];
    }

## Markdown parsing

Let's create a class responsible for reading and processing a given Markdown file, [`src/Posts/MarkdownParser.php`](https://github.com/osmphp/osmcommerce-website/blob/v0.1/src/Posts/MarkdownParser.php):

    <?php
    
    declare(strict_types=1);
    
    namespace My\Posts;
    
    use Osm\Core\App;
    use Osm\Core\Object_;
    
    /**
     * @property string $path
     *
     * @property string $root_path
     * @property string $absolute_path
     * @property bool $exists
     * @property string $original_text
     * ...
     */
    class MarkdownParser extends Object_
    {
        protected function get_root_path(): string {
            global $osm_app; /* @var App $osm_app */
    
            return "{$osm_app->paths->data}/posts";
        }
    
        protected function get_absolute_path(): string {
            return "{$this->root_path}/{$this->path}";
        }
    
        protected function get_exists(): bool {
            return file_exists($this->absolute_path);
        }
    
        protected function get_original_text(): string {
            $this->assumeExists();
            return file_get_contents($this->absolute_path);
        }
    
        ...    
    }

This class expects the file path relative to `data/posts` directory (or `sample-data/posts` in the sample application) to be passed into the `path` property during object creation, after that you can retrieve various information about the Markdown file through its computed properties.

**Q**. `Markdown::$root_path` uses a new `$osm_php->paths->data` property. What module should it be defined in?

`Osm_Framework_Paths`.

## Unit testing the Markdown parser

Let's unit-test the Markdown parsing code:

1. Create a file to be parsed, `sample-data/posts/21/05/18-welcome.md`.

2. Create a unit test, [`tests/test_01_markdown_parsing.php`](https://github.com/osmphp/osmcommerce-website/blob/v0.1/tests/test_01_markdown_parsing.php):

        <?php
        
        declare(strict_types=1);
        
        namespace My\Tests;
        
        use My\Posts\MarkdownParser;
        use Osm\Framework\TestCase;
        
        class test_01_markdown_parsing extends TestCase
        {
            public string $app_class_name = \My\Samples\App::class;
        
            public function test_title() {
                // GIVEN a `welcome.md` file
        
                // WHEN you parse it
                $file = MarkdownParser::new(['path' => '21/05/18-welcome.md']);
        
                // THEN its title is extracted
                $this->assertTrue($file->exists);
                $this->assertEquals('Welcome!', $file->title);
            }
        }

3. Until the Markdown parser handles all the specified edge cases: make the test green, refactor source code, make the test green, ...

The main ideas used in the parser are described in the remaining sections.

## External Composer packages

The parser code uses `nesbot/carbon` package for handling dates, and `michelf/php-markdown` for converting Markdown syntax to HTML.

1. Add the packages to the `composer.json` file: 

        ...
        "require": {
            ...
            "nesbot/carbon": "^2.48",
            "michelf/php-markdown": "^1.9"
        },
        ...
    
2. Run `composer update`. 

## Computed properties

The `osmphp/core` package introduces the concept of a "computed" property. A computed property of a class is a public property whose value is computed on first access by calling a `get_{property}()` method. 

The parser exposes various parts of the original file via its computed properties: `title`, `url_key`, `created_at` and more. If the caller uses these properties, those parts of the Markdown are parsed, if not - then not.

## Regular expressions

Most parsing is done using regular expressions and `preg_replace_callback()` function.

## See Also

* [Applications and modules](/todo)
* [Configuration](/todo)
* [Environment](/todo)
* [Creating a module](/todo)
* [Computed properties](/todo)
* [Unit testing](/todo)
