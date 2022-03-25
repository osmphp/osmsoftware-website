# Field Control Behavior

In Osm Admin, I've finished implementing editing behavior of input and select controls. 

For other control types, a step-by-step guide is prepared.

Full story:

{{ toc }}

### meta.abstract

In Osm Admin, I've finished implementing editing behavior of input and select controls.

For other control types, a step-by-step guide is prepared.

## Editing Behavior

Previously, I finished rendering forms for all three use cases:

* creating a new object - `GET /create`
* editing an existing object - `GET /edit?id=2`
* editing multiple objects - `GET /edit?id=2+3` 

Now it's time to make these forms work. I'll mainly work on a form that edits a single object, and then check and adjust the other use cases.

Let's begin.

### JS Controllers

The browser console complains about undefined `form`, `input-field`, and `select-field` JS controllers. Let's define them. 

I walked through creating a JS controller while [working on facets](17-data-option-and-faceted-attribute-combo-works.md#adding-facet-option-js-behavior). Check this piece for the details, I'll omit them here.

Also, I implemented this logic in `v0.1`, and here, I only copy it to `v0.2` and creatively adapt.

### Form Data

On submit, the form collects data to be submitted from its fields:

    get data() {
        let data = {};

        this.fields.forEach(field => field.data(data));

        return data;
    }

To check form data, select the form element, and type in the browser console:

    $0.osm_controllers['form'].data

### Controller Options

A JS controller reads its options from the `data-js-{name}` attribute. 

For example, the `data-js-form` attribute is:

    data-js-form='{
        "s_saving": "Saving Blue Dress ...",
        "s_saved": "Blue Dress saved successfully.",
        "s_deleting": "Deleting Blue Dress ...",
        "s_deleted": "Blue Dress deleted.",
        "delete_url": "http://admin2.local/admin/products/?id=2"
    }'

These options are written to the `options` property of JS controller object:

    $0.osm_controllers['form'].options 

For better code completion, document JS controller options using JSDoc syntax:

    /**
     * @property {string} options.s_saving Message shown while the object(s)
     *      is being saved
     * @property {string} options.s_saved Message after the object(s) is
     *      successfully saved
     * @property {?string} options.delete_url Absolute URL that handles object
     *      deletion
     * @property {?string} options.s_deleting Message shown while the object(s)
     *      is being deleted
     * @property {?string} options.s_deleted Message after the object(s) is
     *      successfully deleted
     */
    export default register('form', class Form extends Controller {
        ...
    });

### Changed State

A field doesn't add its value to the form data if:

* It's a new object, and the user wants to use the default property value.
* It's an existing object(s), and the user haven't changed the current value.

Either way, the field fetches the initial value when it's first rendered, and it only submits its value if it's differs from the initial value.

For a simple field, such as input or select, the value is the value of the underlying HTML form element. For more complex fields, such as an inline grid, the value may be way more complex, for example, a combination of added rows, changed rows, and deleted rows. I'll return to complex fields later.

Every modified field displays the `Modified. Reset to initial value` button (pencil icon). After pressing the button, the field restores to the initial state.

### Cleared State

On a multi-edit form, a field may display multiple values. In this case, it shows `<multiple values>` text, and the `Clear all values` button (trash icon).   

After pressing this button, the actual form element appears with the default value.

Some complex fields, for example, an inline grid, don't support multi-value editing.

## How To Create A Field Control  

Having input and select fields working as expected, let's sum up what should be in the implementation of any field.

Also, let's take the input field as reference implementation.

### Blade Template

1. Attach dynamic behavior using a `data-js-*` attribute: 

        <div ... data-js-input-field='{!! \Osm\js($js)!!}'>...</div>

2. If displaying `multiple` values, add a `.field__multiple` element containing readonly `<input>`. In most cases, it's enough to include `ui::form.field.multiple` template:

        @include ('ui::form.field.multiple')

3. For a single value editing, add a `.field__multiple` element containing form element(s) of your choosing. Make it hidden if displaying `multiple` values:

        <div class="field__single relative @if ($multiple) hidden @endif">
            ...
        </div>

4. Add `.field-actions` element containing field action buttons, and add `.field__reset-initial-value` button that is displayed when the field is modified:

        <div class="field__actions flex absolute inset-y-0 right-2 my-1">
            <button class="field__action field__reset-initial-value hidden
                flex items-center p-2 text-gray-600"
                title="{{ \Osm\__("Modified. Reset initial value") }}"
                tabindex="-1" type="button"
            >
                <i class="fas fa-pencil-alt"></i>
            </button>
        </div>

### Server-Side View

1. In most cases, extend `Field` view:

        #[Type('input')]
        class Input extends Field
        {
            public string $template = 'ui::form.field.input';
            ...
        }  

2. Provide template data in `get_data()` method. If not extending `Field` view, pass the following mandatory data:

        protected function get_data(): array {
            return [
                'name' => $this->name,
                'multiple' => $this->multiple,
                'js' => [
                    'edit' => $this->form->edit,
                    'multiple' => $this->multiple,
                ],
                ...
            ];
        }

### JS Controller

1. Extend `Field` controller.
2. Call `this.updateActions()` whenever value changes.
3. Submit modified field data in the `data()` method.
4. Fetch initial value `onAttached`. 
5. Report whether the current value differs from the initial value in `changed`. Bear in mind that if `cleared_all_values` the field is considered modified.
6. After submit, re-fetch the initial value in `accept()`.

Example:

    import Field from "../Field";
    import {register} from '../../../js/scripts';
    
    export default register('input-field', class Input extends Field {
        get events() {
            return Object.assign({}, super.events, {
                // event selector
                'input .field__single-input': 'onInput',
            });
        }
    
        data(data) {
            if (this.changed) {
                data[this.name] = this.value;
            }
        }
    
        onAttached() {
            this.initial_value = this.input_element.value;
            this.initial_input_padding_right = parseFloat(
                getComputedStyle(this.input_element).paddingRight);
    
            super.onAttached();
    
            requestAnimationFrame(() => {
                this.updateActions();
            });
        }
    
        get changed() {
            return this.cleared_all_values ||
                this.input_element.value !== this.initial_value;
        }
    
        reset() {
            this.input_element.value = this.initial_value;
        }
    
        accept() {
            if (!(this.cleared_all_values || this.changed)) {
                return;
            }
    
            this.initial_value = this.input_element.value;
            this.options.multiple = false;
            this.onResetInitialValue();
        }
    
        get value() {
            const value = this.input_element.value.trim();
    
            return value !== '' ? value : null;
        }
    
        get name() {
            return this.input_element.name;
        }
    
        get input_element() {
            return this.element.querySelector('.field__single-input');
        }
    
        onInput() {
            this.updateActions();
        }
    
        updateActions() {
            super.updateActions();
    
            this.input_element.style.paddingRight =
                (this.initial_input_padding_right +
                    this.actions_element.offsetWidth) + "px";
        }
    }); 
    
