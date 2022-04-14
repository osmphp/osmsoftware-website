# Automated Migrations. Storing Migrated Schemas

More dots got connected in the schema migration design, see below.

Then, I tried to return to TDDing it, but strange errors met me there, so I left them to be resolved in the next piece.  

{{ toc }}

### meta.abstract

More dots got connected in the schema migration design, see this post for details.

Then, I tried to return to TDDing it, but strange errors met me there, so I left them to be resolved in the next piece.

## Automated Migrations

Yesterday, I wrote:

> For this reason, it's better to run migrations not on file change, but on page refresh in the browser (hitting `GET /products/`, `GET /products/create` or `GET /products/edit` routes).

Well, it can be any HTTP route, any CLI command or any queued job that uses data from `$osm_app->schema`.

`$osm_app->schema` is a cached property. In development, it's reloaded pretty often. Under `gulp watch` it happens after any change in source code. In production, it's reloaded only during application update, or after running the `osm refresh` command. 

After schema is loaded, Osm Admin will check if it's still the same as of the latest migration in the database. If it's not, the application will fail in production, and it will run the migrations in development (if the `AUTO_SCHEMA_MIGRATIONS` environment variable is set).

If several processes try to run automated schema migrations, only the first one runs, and then rest wait for it to finish (waiting for a lock to be released).

## How Current Schema Is Stored In DB

Well, it seems that there is no need to store every applied schema, or every individual migration. It's enough to store the currently applied schema JSON.

And it's already implemented! It stored in the only row of the `schema` table, in the `current` column. More columns are yet to come, but this one has to stay.

## Filesystem Stores Schema JSONs, Not PHP Files 

In a similar fashion, `osm generate:schema` saves the schema JSON from the codebase to `migrations/{app_name}/0000001.json`. 

