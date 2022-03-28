# Fixing Progress Messages And Preventing Unsafe Operations

Progress messages stopped working, and I fixed that. Then, I implemented a safety measure that prevent accidental deletion (or other operations) on all objects. 

Contents:

{{ toc }}

### meta.abstract

Progress messages stopped working, and I fixed that. Then, I implemented a safety measure that prevent accidental deletion (or other operations) on all objects.

## Messages

The nice progress messages that I developed in `v0.1`, doesn't work in `v0.2`.

The message HTML markup was added using a Blade `@around` directive:

    @around({{ $footer ?? '' }})
    
And this line is not there anymore, and message HTML markup is not added anymore. Fixed it by changing to:

    @around(@include('std-pages::footer'))
    
## Preventing Unsafe Operations

While it's OK to show all products on the `GET /` page, it's irresponsible to allow editing all products with just `GET /edit` and `POST /`, and it's plain dangerous to allow deleting all products with just `DELETE /`.

Let's require additional `?all` flag in the URL on all dangerous routes.

Let's mark safe routes with new `#[Safe]` attribute, and call new `assertSafe()` method in every route to check that there are either URL filters or `?all` flag:

    // Routes\Admin\EditPage
    public function run(): Response
    {
        $this->assertSafe($this->form_view->query);
        ...
    }
 
    // Routes\Route
    protected function assertSafe(Query $query): void {
        if ($this->safe) {
            return;
        }

        if (!empty($query->filters)) {
            return;
        }

        if (($this->http->query['all'] ?? null) === true) {
            return;
        }

        throw new UnsafeOperation(__(
            "Specify a filter in the URL query parameters, or confirm an operation on all objects using `?all` flag."));
    }

The `UnsafeOperation` operation exception sends `403 Forbidden` response:

    class UnsafeOperation extends Http
    {
        public function response(): Response
        {
            global $osm_app; /* @var App $osm_app */
    
            return $osm_app->http->responses->forbidden($this->getMessage());
        }
    }    
    

