# What's Left. Menu View. Home Page

It's been 3 weeks after my last commit - I've been ill. And now I feel like a total newbie - I look at the codebase, and I don't know where to continue my last thought. Let's start slow, and gradually get up-to-speed.

Today, I reiterated on what's left in this iteration, and finished the side menu view, and created a trivial home page for the admin area.

Details:

{{ toc }}

### meta.abstract

Today, I reiterated on what's left in this iteration, and finished the side menu view, and created a trivial home page for the admin area.

## What's Left

The iteration goal is to shoot a video that shows how much fun it is to create applications using Osm Admin.

I've already made it fairly easy to create and run a project, create and alter a data class and see table/form UI for it.  

Now I'm working on a menu that allows entering table/form UI of every data class defined in the application.

**Later**. Next, I want to add a page header and fix how the sidebar behaves on mobile.

Then, I want to experiment with GitPod and automate setting up of the development environment. 

Finally, a video should come out demonstrating all that.

After the iteration is over, I'll pick one of the following improvement fronts: DX, Schema, Indexing, UI, API.  

## Menu

Last time, I created a menu view, empty, without any items, and added it to the sidebar of the admin area. 

### Template

It doesn't have a template yet, so let's define one.

The view class mentions its name:

    class Menu extends View
    {
        public string $template = 'ui::menu';
        ...
    }

It maps to the `themes/_admin__tailwind/views/ui/menu.blade.php` filename. Here is the first attempt:

    <?php
    /* @var \stdClass[]|\Osm\Admin\Ui\Hints\MenuItem[] $items */
    ?>
    
    <aside class="overflow-hidden shadow-md sm:rounded-lg my-4 mr-8">
        <h2 class="text-xl px-4 py-2 bg-gray-50 dark:bg-gray-700">{{ \Osm\__("Menu") }}</h2>
        <ul class="text-sm px-4 py-2 text-gray-500 dark:text-gray-400">
            @foreach ($items as $item)
                <li class="p-2 my -mx-2">
                    <a class="block" href="{{ $item->url }}"
                        title="{{ $item->title }}"
                    >
                        <span>{{ $item->title }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </aside>
    
Just like a facet in the sidebar, it renders a list of items. 

**Later**. For now, the list of items is hard-coded:

    protected function get_data(): array {
        return [
            'items' => [
                'products' => (object)[
                    'title' => 'Products',
                    'url' => ui_query(Product::class)
                        ->toUrl('GET /'),
                ],
            ],
        ];
    }

**Later**. For now, the menu is non-hierarchical.

### Item Data

Let's infer the menu items from the codebase:

    // Osm\Admin\Ui\Menu
    
    /**
     * ...   
     * @property MenuItem[] $items #[Serialized]
     * ...   
     */
    class Menu extends View
    {
        ...
        protected function get_items(): array {
            throw new Required(__METHOD__);
        }
        
        protected function get_data(): array {
            return [
                'items' => $this->items,
            ];
        }
    }
    
    // Osm\Admin\Ui\MenuItem
    
    /**
     * @property string $title
     * @property string $url
     */
    class MenuItem extends Object_
    {
        protected function get_title(): string {
            throw new Required(__METHOD__);
        }
    
        protected function get_url(): string {
            throw new Required(__METHOD__);
        }
    }
    
    // Osm\Admin\Ui\MenuItem\Table
    
    /**
     * @property string $table_name #[Serialized]
     * @property SchemaTable $table
     *
     * @uses Serialized
     */
    class Table extends MenuItem
    {
        protected function get_table_name(): string {
            throw new Required(__METHOD__);
        }
    
        protected function get_table(): SchemaTable {
            global $osm_app; /* @var App $osm_app */
    
            return $osm_app->schema->tables[$this->table_name];
        }
    
        protected function get_title(): string
        {
            return __($this->table->s_objects);
        }
    
        protected function get_url(): string
        {
            return ui_query($this->table_name)->toUrl('GET /');
        }
    }
    
    // Osm\Admin\Schema\Schema
    
    protected function get_menu(): Menu {
        $items = [];

        foreach ($this->tables as $table) {
            $items[] = MenuItem\Table::new([
                'table_name' => $table->name,
            ]);
        }

        return Menu::new(['items' => $items]);
    }

    // Osm\Admin\Ui\Sidebar
        
    protected function get_menu(): Menu|View {
        global $osm_app; /* @var App $osm_app */

        return view($osm_app->schema->menu);
    }

## Home Page

So far, so good. 

**Later**. One more thing: there should be some home page maybe with some dashboard, or main KPIs on it. 

For now, let's just create an empty home page with the side menu on it:

    // Osm\Admin\Ui\Routes\Admin\HomePage
    
    /**
     * @property Home $home
     */
    #[Area(Admin::class), Name('GET /')]
    class HomePage extends Route
    {
        protected function get_home(): Home|View {
            return view(Home::class);
        }
    
        public function run(): Response
        {
            return view_response($this->home->template, $this->home->data);
        }
    }
    
    // Osm\Admin\Ui\Home
    
    class Home extends View
    {
        public $template = 'ui::home';
    
        protected function get_data(): array
        {
            return [
                'title' => __("Home"),
                'sidebar' => view(Sidebar::class),
            ];
        }
    }

    // themes/_admin__tailwind/views/ui/home.blade.php
        
    <?php
    /* @var string $title */
    ?>
    
    @extends('ui::layout')
    @section('title', $title)
