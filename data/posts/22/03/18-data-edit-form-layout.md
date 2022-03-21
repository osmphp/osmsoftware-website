# Edit Form Layout

After getting the list page to work, I returned to the editing page. 

Done:

* new page layout based on template inheritance
* form header
* form field layout

Here is how it went:

{{ toc }}

### meta.abstract

After getting the list page to work, I returned to the editing page.

Done:

* new page layout based on template inheritance
* form header
* form field layout

## Adapting To New Page Layout

I've taken form Blade templates from `v0.1`. Of course, it doesn't work - the underlying data structures have changed, and so did the page layout.

Let's adapt it.

The first error I get is 

    Unable to locate a class or view for component [std-pages::layout]
    
That's because I don't use a Blade component for page layout anymore. Instead, I use Blade template inheritance. Here is the fix:

    @extends('ui::layout')
    @section('title', $title)
    @section('main')
        ...
    @endsection

## Form Header

`Form::$data` property returns template variables:

    protected function get_data(): array {
        return [
            'form' => $this,
            'table' => $this->table,
            'result' => $this->result,
            'title' => $this->title,
            'save_url' => $this->save_url,
            'close_url' => $this->query->toUrl('GET /'),
            'count' => $this->count,
            'js' => [

            ],
        ];
    }

In the template opening comment, the template variables are type-hinted.

It's a good practice to list all variables on the template opening comment (`themes/_admin__tailwind/views/ui/form.blade.php`): 

    <?php
    /* @var \Osm\Admin\Ui\Form $form */
    /* @var \Osm\Admin\Schema\Table $table */
    /* @var \Osm\Admin\Ui\Result $result */
    /* @var string $title */
    /* @var string $save_url */
    /* @var string $close_url */
    /* @var int $count */
    /* @var array $js */
    ?>

Finally, updated template markup became a lot shorter:

    <div class="container mx-auto px-4 grid grid-cols-12">
        <section class="col-start-1 col-span-12">
            <form method="POST" action="{{ $save_url }}"
                autocomplete="off"
                data-js-form='{!! \Osm\js($js) !!}'>

                <h1 class="text-2xl sm:text-4xl pt-6 mb-6 border-t border-gray-300">
                    {{ $title }}
                </h1>
                <div>
                    <a href="{{ $close_url }}"
                        class="text-white bg-blue-700
                            hover:bg-blue-800 focus:ring-4 focus:ring-blue-300
                            font-medium rounded-lg text-sm px-5 py-2.5 text-center
                            mr-3 mb-3">{{ \Osm\__("Close")}}</a>
                    <button type="submit"
                        class="text-white bg-blue-700
                            hover:bg-blue-800 focus:ring-4 focus:ring-blue-300
                            font-medium rounded-lg text-sm px-5 py-2.5 text-center
                            mr-3 mb-3">{{ \Osm\__("Save")}}</button>
                    @if ($count > 0)
                        <button type="button"
                            class="form__action -delete text-white bg-red-700
                                hover:bg-red-800 focus:ring-4 focus:ring-red-300
                                font-medium rounded-lg text-sm px-5 py-2.5 text-center
                                mr-3 mb-3">{{ \Osm\__("Delete")}}</button>
                    @endif
                </div>
            </form>
        </section>
    </div>

## Form Layout

A form display *fields*, grouped into *fieldsets* that are grouped into *sections* that are grouped into *chapters*:

    form
        chapter
            section
                fieldset
                    field
                    
Visually, sections are displayed as tabs, and chapters serve as tab groups. If there is only a single default section, tabs are not shown. 

A tab displays fields, and fieldsets serve as field groups. If there is a single default fieldset, the fieldset wrapper is not shown either.

A form has a `layout` - an array that defines chapter/section/fieldset/field structure. The default form layout is made of one chapter, one section, and one fieldset:

    protected function get_layout(): array {
        return [
            // default chapter
            '' => [
                'layout' => [
                    // default section
                    '' => [
                        'layout' => [
                            // default fieldset
                            '' => [
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

Every property pops into some fieldset. If not specified, it is a part of the default fieldset.

The implementation of the form layout is completed, however the code is rather mundane and repetitive, not worth digging.

 