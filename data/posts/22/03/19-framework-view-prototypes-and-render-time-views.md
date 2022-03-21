# View Prototypes And Render-Time Views

A `View` is a short-living object that is created and computed during page rendering. You can also create a pre-configured a `View` object prototype in advance, and then clone it for rendering. 

It's important not to execute render-time properties while configuring the prototype. 

Use `view()` helper function to create render-time view instances, and mark render-time properties using `#[RenderTime]` attribute to prevent accessing them before rendering. 

Here is how I came up to that:
 
{{ toc }}

### meta.abstract

A `View` is a short-living object that is created and computed during page rendering. You can also create a pre-configured a `View` object prototype in advance, and then clone it for rendering.

It's important not to execute render-time properties while configuring the prototype.

Use `view()` helper function to create render-time view instances, and mark render-time properties using `#[RenderTime]` attribute to prevent accessing them before rendering.

## Problem

In Osm Admin, the form field displays a property value, or a value returned by a formula. Either way, while preparing a query, the field adds the formula to a query using `$query->select()` method:

    public function prepare(Query $query): void
    {
        $query->select($this->formula);
    }

Every formula has an associated control that specifies the editing markup and behavior. But for some reason, when accessing the parsed formula, I get an error saying that `Query::$http_query` property is not set.

What's going on?

## Solution

You see, there are actually two `Form` objects. One is a part of the schema, it's stored in the `Table::$form` property. The other is created by cloning the form from the schema using the `view()` function in the `EditPage` route:

    protected function get_form_view(): Form|View {
        $form = view($this->table->form_view);

        $form->http_query = $this->http->query;

        return $form;
    }

The idea here is that the former is a preconfigured form prototype, while the latter is created every time the form is actually rendered. Render-time propertied should only be evaluated on the view created using the `view()` function.  

To distinguish the two, I added `View::$rendering` property that is only true if a view is created using the `view()` helper function, and `#[RenderTime]` attribute for marking properties that can only be touched during rendering.

And indeed, the code in the `Field` render-time properties access the form object from schema, not the one created for rendering.

The problem is that the whole form/.../field view tree is stored in the schema, and cloning the form object in the `view()` function should also clone all child views.

I've changed the `view()` function accordingly, and, indeed, it helped.

 