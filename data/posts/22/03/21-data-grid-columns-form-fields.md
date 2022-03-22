# Grid Columns, Form Fields

On the list page, grid columns are implemented as [control views](20-data-control-views.md). On the editing page, form fields are also implemented as control views.

The form Blade templates are completely migrated to Osm Admin `v0.2`.

Below are all the tiny details:
   
{{ toc }}

### meta.abstract

On the list page, grid columns are implemented as *control views*. On the editing page, form fields are also implemented as control views.

The form Blade templates are completely migrated to Osm Admin `v0.2`.

## Fixing Grid Page

After implementing [control views](20-data-control-views.md), the list page doesn't work anymore. It happens to me all the time, I rarely see anything but an exception stack trace.

Let's push through the errors one by one.

### Hidden Property Has No Control

    Formula\SelectExpr::get_control(): Return value must be of type 
    Osm\Admin\Ui\Control, null returned

`Property::$control` is null if`#[Hidden]` attribute is applied, so let's make it nullable.

### Updated `ui::grid` Template

Column headers are rendered using standard `View` properties:

    @foreach ($grid->columns as $column)
        @include($column->template, $column->data)
    @endforeach
 
Column cells are rendered using additional `cell_template` property, and the `data()` method:

    @foreach ($grid->columns as $column)
        @include($column->cell_template,
            $column->data($item))
    @endforeach

### Facet Title Is Not Rendered

`Control::$title` is no longer there, I moved it to `Property::$title`.

## Form Fields

The form view is created in the `EditPage` controller:

    protected function get_form_view(): Form|View {
        return view($this->table->form_view, [
            'http_query' => $this->http->query,
        ]);
    }

The `view()` clones the form view from its cached prototype, and its child views, recursively. 

In `Grid`, `columns` is a `#[RenderTime]` property computed from the `Ui\Query`:

    protected function get_columns(): array {
        $columns = [];

        foreach ($this->query->selects as $alias => $select) {
            if ($select->control) {
                $columns[$alias] = view($select->control->grid_column, [
                    'grid' => $this,
                    'name' => $alias,
                ]);
            }
        }

        return $columns;
    }

Unlike `Grid::$columns`, `Fieldset::$fields` is computed when putting the schema into cache: 

    protected function get_fields(): array {
        $fields = [];

        foreach ($this->form->struct->properties as $property) {
            if (!$property->control) {
                continue;
            }

            $fields[$property->name] = $field =
                clone $property->control->form_field;

            $field->fieldset = $this;
            $field->name = $property->name;
            $field->formula = $property->name;
        }

        return $fields;
    }

The implementation differs because the new object page will use the same form, but there will be no underlying query.

### Input Field Template

OK, form field views have found their place in the schema, and they are properly instantiated. The next step is to adapt the Blade template from Osm Admin `v0.1`.

Here it goes:

    <?php
    /* @var string $name */
    /* @var string $title */
    /* @var string $value */
    /* @var bool $multiple */
    /* @var array $js */
    ?>
    <div class="field grid grid-cols-12 mb-6"
        data-js-input-field='{!! \Osm\js($js)!!}'
    >
        <label for="{{ $name }}"
            class="col-start-1 col-span-12 md:col-start-1 md:col-span-3
                mb-2 md:mb-0 flex items-center
                text-sm font-medium text-gray-900"
        >
            <span>
                {{ $title }}
            </span>
        </label>
        <div class="col-start-1 col-span-12 md:col-start-4 md:col-span-9">
            <div class="relative">
                <input type="text" name="{{ $name }}" id="{{ $name }}"
                    class="bg-gray-50 border border-gray-300 rounded-lg p-2.5 w-full
                        text-gray-900 sm:text-sm
                        focus:ring-blue-500 focus:border-blue-500"
                        value="{{ $value }}"
                    @if ($multiple)
                        placeholder="{{ \Osm\__("<multiple values>")}}"
                    @endif
                >
                <div class="field__actions flex absolute inset-y-0 right-2 my-1">
                    @if ($multiple)
                        <button class="field__action field__clear flex items-center p-2 text-gray-600"
                            title="{{ \Osm\__("Clear all values") }}"
                            tabindex="-1" type="button"
                        >
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    @endif
                    <button class="field__action field__reset hidden flex items-center p-2 text-gray-600"
                        title="{{ \Osm\__("Modified. Reset to initial value") }}"
                        tabindex="-1" type="button"
                    >
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
Later, I'll return to:

* `value` variable, it's currently presumes `string` data type.
* `multiple` variable is always false.
* `js` array is empty.

### Select Field Template

...is similar to the input field template. 

Instead of `<input>`, it uses `<select>`:

    <select name="{{ $name }}" id="{{ $name }}"
        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm
            rounded-lg focus:ring-blue-500 focus:border-blue-500
            block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600
            dark:placeholder-gray-400 dark:text-white
            dark:focus:ring-blue-500 dark:focus:border-blue-500"
    >
        <option value="" @if ($value === '') selected @endif></option>
        @foreach ($options as $option)
            <option value="{{ $option->value}}"
                @if ($value === $option->value) selected @endif
            >{{ $option->title }}</option>
        @endforeach
    </select>

Later, I'll consider using `<input>` for entering it part of its title and searching the option in a popup.

It's also an open question how should `<select>` behave when editing multiple values.    

### Result

Here is the result:

![Edit Form](edit-form.png)

Thanks again, [FlowBite](https://flowbite.com/), for the design.