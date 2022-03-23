# Multi-Editing Form. New Object Form. `osmphp/extras` Package 

In Osm Admin, object editing form is used not only for a single object editing, but also for editing multiple objects, and for creating a new object.

I also created the [`osmphp/extras` package](https://github.com/osmphp/extras) as an easy way of adding [Font Awesome](https://fontawesome.com/) icons to a project. Later, I'll add more optional reusable goodies to this package.   

As always, step-by-step:

{{ toc }}

### meta.abstract

In Osm Admin, object editing form is used not only for a single object editing, but also for editing multiple objects, and for creating a new object.

I also created the *`osmphp/extras` package* as an easy way of adding *Font Awesome* icons to a project. Later, I'll add more optional reusable goodies to this package.

## Multi-Editing

Before delving into edit form behavior, let's finish with rendering:

* a form that allows editing of several objects at once (*multi-editing*)
* a form for creating new objects

A field shows multiple values if the underlying query returns more than one object and the field property has different values in those objects.

Let's request the editing form for two objects:

    /products/edit?id=2+3 

### Form Title

The first thing to decide is the form title. If there is more than one object, let's display the title of one product and the number of the rest products in the title:

    Blue Dress And 1 More Product(s) 

Here is the implementation:

    // Form
    protected function get_title(): string {
        ...
        return __($this->table->s_title_and_n_more_object_s, [
            'title' => $this->result->items[0]->title,
            'count' => $this->count - 1,
        ]);
    }

### Merging Values

If a field shows multiple values, its name is in the `Form::$item->_multiple` array, otherwise its value is in the `Form::$item` object:

    // Form
    
    protected function get_merge(): bool {
        return $this->count > 1 && $this->count <= static::MAX_MERGED_RECORDS;
    }

    protected function get_item(): \stdClass {
        ...
        $merged = (object)[
            '_multiple' => [],
        ];

        foreach ($this->chapters as $chapter) {
            $chapter->merge($merged);
        }

        return $merged;
    }
    
    // Field
    
    public function merge(\stdClass $merged): void
    {
        if (!$this->form->merge) {
            $merged->_multiple[] = $this->name;
            return;
        }

        $value = null;
        foreach ($this->form->result->items as $index => $item) {
            if ($index == 0) {
                $value = $item->{$this->name} ?? null;
                continue;
            }

            if ($value !== ($item->{$this->name} ?? null)) {
                $merged->_multiple[] = $this->name;
                return;
            }
        }

        if ($value !== null) {
            $merged->{$this->name} = $value;
        }
    }

## `osmphp/extras` Package

FontAwesome icons are not rendered - as the resource files are not migrated from Osm Admin `v0.1`. It's not the first time that I use these icons - let's make a Composer package and reuse them in any project.

To be exact, the new package will be a place for any extra stuff that I use in my projects, or recommend to use in your projects.

### Creating A Package

Create a project as described [here](https://osm.software/docs/framework/0.15/getting-started/installation.html):

    cd ~/projects
    composer create-project osmphp/project extras
    cd extras
    bash bin/install.sh

### Package Name

1. Delete sample code from `src/` and `themes/`.

2. Edit the package name, description, and PHP namespace in the `composer.json`:

        {
            "name": "osmphp/extras",
            "description": "Extra libraries for Osm Framework-based projects",
            ...
            "autoload": {
                "psr-4": {
                    "Osm\\Extras\\": "src/"
                }
            },
            ...
        }

3. Run `composer update`.

### Create `FontAwesome` Module

    // src/FontAwesome/Module.php
    <?php
    
    namespace Osm\Extras\FontAwesome;
    
    use Osm\Core\Attributes\Name;
    use Osm\Core\BaseModule;
    
    #[Name('font-awesome')]
    class Module extends BaseModule
    {
        public static array $requires = [
            \Osm\Framework\Themes\Module::class,
        ];
    }

### Upload Files

1. Copy FontAwesome font files to `themes/_base/fonts/font-awesome` directory.
2. Copy FontAwesome CSS styles to `themes/_base/css/font-awesome/styles.css` file.

### Edit `styles.css`

1. Wrap the whole file into Tailwind CSS directive:

        @layer components {
            ... 
        } 

2. Search & replace through all the `url()` directives, so that they are `url("fonts/font-awesome/...")`. The reason is the directory structure of the public theme directory:

        styles.css           # all CSS files are merged into here
        fonts/               # all font directories are merged into here
            font-awesome/
                ...

### Create GitHub Repository

1. Create the public GitHub repository `osmphp/extras`. `osmphp` organization is configured to create a default branch named `v0.1`.

2. Put the local project under Git, and push it to the new repository:

        git init
        git add .
        git commit -m "first commit"
        git branch -M v0.1
        git remote add origin git@github.com:osmphp/extras.git
        git push -u origin v0.1

### Publish Package

1. Remove `README.md` and commit. Or even better, write a relevant one.
2. Create `v0.1.0` Git tag.
3. Push.
4. On [Packagist](https://packagist.org/), publish the package.

### Use Package

1. Add the new package to your project (in my case, it's Osm Admin):

        composer require osmphp/extras

2. Add the module name to the list of your module dependencies:

        public static array $requires = [
            ...
            \Osm\Extras\FontAwesome\Module::class,
        ];

3. Restart Gulp:

        gulp && gulp watch

## New Object Form

New object form should be displayed on `GET /create` page.

Let's create the route class:

    /**
     * @property Form $form_view
     */
    #[Ui(Admin::class), Name('GET /create')]
    class CreatePage extends Route
    {
        protected function get_form_view(): Form|View {
            return view($this->table->form_view);
        }
    
        public function run(): Response
        {
            return view_response($this->form_view->template, $this->form_view->data);
        }
    }

The edit page set new `load` flag, and the form checks it:

    // EditPage
    protected function get_form_view(): Form|View {
        return view($this->table->form_view, [
            'load' => true,
            'http_query' => $this->http->query,
        ]);
    }
    
    // Form
    public bool $load = false;

    protected function get_query(): Query {
        $query = ui_query($this->table->name);

        if ($this->load) {
            $query
                ->fromUrl($this->http_query,
                    'limit', 'offset', 'order', 'select')
                ->count();
        }

        $query->db_query->select('id', 'title');

        foreach ($this->chapters as $chapter) {
            $chapter->prepare($query);
        }

        return $query;
    }

    protected function get_count(): int {
        return $this->load ? $this->result->count : 0;
    }

    
   
 