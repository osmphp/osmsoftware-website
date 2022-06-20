# Better DX. Feature Branches. Main Menu

After getting the main branch all-green (tests pass, CLI and UI works as expected), I decided that from now on, I'll keep it always green, and use feature branches for all major development.

Then, I started implementing the main menu of the admin area.

More details:

{{ toc }}

### meta.abstract

After getting the main branch all-green (tests pass, CLI and UI works as expected), I decided that from now on, I'll keep it always green, and use feature branches for all major development.

Then, I started implementing the main menu of the admin area.

## Thoughts On Better Developer Experience

I'm really excited that everyone can create a project that uses Osm Admin and actually try out new features.

**Later**. In the future, I'd like it to be even more straightforward. Like one command to install the prerequisites, one command to create a project. Both in development and in production. Ideally, with a UI. However, right now, it's not the most pressing issue.

## Feature Branches

From now on, I'll keep the main branch (currently, `v0.2`) always green. It means that at all times:

* all test suites pass;
* CLI commands work;
* UI works.

In practice, it means that new features will be created on temporary *feature branches*. For example, I'll implement menus on `f-menu` feature branch. Once it works - again, tests, CLI and UI - I'll merge into the main branch.

## Menu

### Idea

And the most pressing issue now is a menu that allows to access product grid, other defined grids, or, for singleton tables, edit forms.

A data class should appear on the menu without any additional effort, in the last top-level menu item, `Other`.

You'll be able to define more groups and assign data classes to menu groups in code. 

### Sidebars

There are two conventional menu positions: 

1. Top menu items at the page top, deeper menu items in popups.
2. Collapsible menu tree in the sidebar.

There may be different themes that utilize either approach. In the default theme, I'll use the sidebar approach, mainly, considering responsive design.

On a mobile device, there is no screen estate for the sidebar - it will show the grid or form full-width. It will slide in from the side using a button in the header, or a touch gesture.

Let's say it works. Even then, there is are too many navigation views fighting for the space in the sidebar:

* menu
* faceted filters
* form section links

They may all appear on the same page, for example, a complex product form with multiple sections showing multiple products and faceted filters. 

One solution is to have two sidebars:

* The left sidebar will show the menu.
* The right sidebar will show the section names and the facets.    

However, on a desktop, the more space is left for the grid, the better. So, I stay with all three navigation views in a single sidebar, each being collapsible.

### View

Let's start with a view that displays some dummy data.

**Notice**. In Osm Framework, a `View` is a short-living object that is created and computed during page rendering. You can also create a pre-configured `View` object prototype in advance, and then clone it for rendering. For more information, see [why and how it was introduced](../03/19-framework-view-prototypes-and-render-time-views.md).

**Later**. Then, I'll fill the model from class reflection, and auxiliary classes.

Currently, the sidebar only displays facets:

    # themes/_admin__tailwind/views/ui/layout.blade.php
    @section('content')
        @if(!empty($sidebar) && $sidebar->visible)
            <div class="container mx-auto px-4 grid grid-cols-12">
                <div class="col-start-1 col-span-3">
                    @include($sidebar->template, $sidebar->data)
                </div>
                ...
            </div>
        @else
            ...
        @endif
    @endsection

    # themes/_admin__tailwind/views/ui/sidebar.blade.php
    ...
    @isset($facets?->visible)
        @include($facets->template, $facets->data)
    @endif

Let's add the menu view:

    # themes/_admin__tailwind/views/ui/sidebar.blade.php
    ...
    @isset($menu?->visible)
        @include($menu->template, $menu->data)
    @endif
    ...
    
    # Osm\Admin\Ui\Sidebar
    /**
     * ...
     * @property Menu $menu
     * ...
     */
    class Sidebar extends View
    {
        ...
        protected function get_visible(): bool {
            return $this->facets?->visible ||
                $this->menu->visible;
        }
    
        protected function get_data(): array {
            return [
                ...
                'menu' => $this->menu,
            ];
        }


        protected function get_menu(): Menu|View {
            return view(Menu::new());
        }
    }
    
    # Osm\Admin\Ui\Menu
    /**
     * Render-time properties:
     *
     * @property bool $visible
     */
    class Menu extends View
    {
        public string $template = 'ui::menu';
    
        protected function get_visible(): bool {
            return true;
        }
    
        protected function get_data(): array {
            return [];
        }
    }
    
