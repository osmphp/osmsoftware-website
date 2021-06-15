# Dynamic routes

This is the 8-th blog post in the "Building `osmcommerce.com`" series. In, this post, you'll handle dynamic HTTP routes.

{{ toc }}

## meta

    {
        "series": "Building osmcommerce.com", 
        "series_part": 8
    }
    
## Blog routes

The blog will have these routes:

* `/blog/` all posts 
    * ordered by creation date
    * infinite scrolling
* `/blog/{year}/{month}/{url_key}.html` post having `url_key` assigned
* `/blog/search?q={search_phrase}` posts matching given `search_phrase` 
    * ordered by relevance
    * infinite scrolling  
* `/blog/{year}/` posts of given `year` 
    * ordered by creation date
    * infinite scrolling
* `/blog/{year}/{month}/` posts of given `month`
    * ordered by creation date
    * infinite scrolling
* `/blog/tags/{tag}/` posts of given `tag` 
    * ordered by creation date
    * infinite scrolling
* `/blog/series/{series}/` posts of given `series`
    * ordered by part number
    * infinite scrolling

Before implementing them, let's dig into how Osm framework routing works.

## How Osm framework routes HTTP requests

In Osm framework, the routing is naive by design, mainly, due to performance considerations. It's implemented in `Osm\Framework\Http\Http` class:

    public function run(): Response {
        return $this->module->around(function() {
            $this->detectArea();

            return $this->module->around(function() {
                $this->detectRoute();

                return $this->route->run();
            }, $this->area_class_name);
        });
    }

First, it detects whether it's `front`, `admin` or `api` area. Each area has its own set of routes.

Then it detects the route. Most routes are static and defined as follows:

    #[Area(Front::class), Name('GET /')]
    class Home extends Route
    {
        public function run(): Response {
            ...
        }
    }

Static route matching is done by the HTTP method and path specified in the `Name` attribute.

If there is no matching static route, it checks whether there is a matching dynamic route. Dynamic routes are defined as follows:

    #[Area(Front::class)]
    class Post extends Route
    {
        public function match(): ?Route {
            ...
        }
    
        public function run(): Response {
            ...
        }
    }

The framework calls `match()` method of every dynamic route until one of them returns a non-null result. 

## Back to blog routes

Again, all the routes:

    /blog/
    /blog/search?q={search_phrase}
    /blog/{year}/{month}/{url_key}.html
    /blog/{year}/
    /blog/{year}/{month}/
    /blog/tags/{tag}/
    /blog/series/{series}/

Dynamic routes are usually handled either by parsing the incoming URL path (often, using regular expressions), by searching the incoming URL path in a database table, or both.

The simpler, the better - let's do regular expressions using the `nikic/fast-route` package.

## Implementing dynamic route

1. Install the routing package:

        composer require nikic/fast-route

2. Create `src/Posts/Routes/Front/Dynamic.php` class handling the dynamic routes:

        <?php
        
        declare(strict_types=1);
        
        namespace My\Posts\Routes\Front;
        
        use FastRoute\Dispatcher;
        use FastRoute\RouteCollector;
        use Osm\Framework\Areas\Attributes\Area;
        use Osm\Framework\Areas\Front;
        use Osm\Framework\Http\Route;
        use function FastRoute\simpleDispatcher;
        
        /**
         * @property Dispatcher $dispatcher
         * @property string $prefix
         */
        #[Area(Front::class)]
        class Dynamic extends Route
        {
            public function match(): ?Route {
                $dispatched = $this->dispatcher->dispatch(
                    $this->http->request->getMethod(), $this->http->path);
        
                if ($dispatched[0] !== Dispatcher::FOUND) {
                    return null;
                }
        
                $new = "{$dispatched[1]}::new";
        
                return $new($dispatched[2]);
            }
        
            protected function get_dispatcher(): Dispatcher {
                return simpleDispatcher(function (RouteCollector $r) {
                    $r->addGroup($this->prefix, function (RouteCollector $r) {
                        $this->collectRoutes($r);
                    });
                });
            }
        
            protected function collectRoutes(RouteCollector $r): void {
                $r->get('/', RenderAllPosts::class);
                $r->get('/search', RenderSearchResults::class);
                $r->get('/{year:\d+}/{month:\d+}/{url_key}.html',
                    RenderPost::class);
                $r->get('/{year:\d+}/', RenderYearPosts::class);
                $r->get('/{year:\d+}/{month:\d+}/', RenderMonthPosts::class);
                $r->get('/tags/{tag}/', RenderTagPosts::class);
                $r->get('/series/{series}/', RenderSeriesPosts::class);
            }
        
            protected function get_prefix(): string {
                return '/blog';
            }
        }

3. Create all the mentioned route classes - extend them from the `Osm\Framework\Http\Route` class.

## How the dynamic routing works

The `Dynamic` class adds all route definitions to a dispatcher object. Under the hood the dispatcher object creates a regular expression.

Then the `Dynamic` class calls the `dispatch()` method of the dispatcher object. Under the hood, the dispatcher objects checks whether the incoming URL path matches the regular expression.

If it matches, a route object is created and filled in with dynamic parameters like `year` or `url_key`. After that, the framework calls the `run()` method of that route object.

## Adding trailing slash

Often, users don't distinguish routes with or without trailing slash (`/blog` and `/blog/`), and use them interchangeably. A good SEO practice advises implementing only one of the variants, and redirect the other one.

In the project, the routes with trailing slash are handled, so let's redirect the routes without trailing slash: 

1. Add routes without the trailing slash:

        protected function collectRoutes(RouteCollector $r): void {
            $r->get('', AddTrailingSlash::class);
            $r->get('/{year:\d+}', AddTrailingSlash::class);
            $r->get('/{year:\d+}/{month:\d+}', AddTrailingSlash::class);
            $r->get('/tags/{tag}', AddTrailingSlash::class);
            $r->get('/series/{series}', AddTrailingSlash::class);
            ...
        }

2. Implement the `AddTrailingSlash` route:

        <?php
        
        declare(strict_types=1);
        
        namespace My\Posts\Routes\Front;
        
        use Osm\Framework\Http\Route;
        use Symfony\Component\HttpFoundation\RedirectResponse;
        use Symfony\Component\HttpFoundation\Response;
        
        class AddTrailingSlash extends Route
        {
            public function run(): Response {
                return new RedirectResponse(
                    "{$this->http->base_url}{$this->http->path}/" .
                    "{$this->http->request->server->get('QUERY_STRING')}", 301);
            }
        }
