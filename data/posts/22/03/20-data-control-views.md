# Control Views

I redesigned control rendering on list and edit pages. From now, a control contains view prototypes for a grid column, a form field, and other.

Below is the reasoning.

Currently, a `Control` is an object that can display a property or formula on a list page or in a form. In a grid, it provides a `header_template` to render a column header, and `cell_template` to render a cell:

    @foreach ($grid->columns as $column)
        @if ($column->cell_template)
            @include($column->cell_template, [
                'column' => $column,
                'item' => $item,
            ])
        @endif
    @endforeach

A specific control object is cloned during rendering:

    protected function get_columns(): array {
        $columns = [];

        foreach ($this->query->selects as $select) {
            $control = $select->expr instanceof Formula\Identifier
                ? $select->expr->property->control
                : $select->data_type->default_control;

            if (!$control) {
                continue;
            }

            $columns[$select->alias] = $control = clone $control;
            $control->name = $select->alias;
            $control->view = $this;
        }

        return $columns;
    }

It reminds me `View` objects. 

**Q**. Maybe, `Control extends View`? 

No. `View` has a single `template`, so I should have a separate `View` class for column header, for a cell, for a form field, and so on.  

**Q**. Isn't creating a view object for every cell in a grid an overkill?

It is. While I can render a column header from a view, it's better to use the same view object for rendering column cells, too:

    @foreach ($grid->columns as $column)
        @include($column->cell_template, $column->data($item))
    @endforeach

**Q**. How does schema change?

Control objects stay in their places:

* `DataType::$control` defines the default control settings for a data type.
* `Property::$control` clones `DataType::$control`, or creates a control object from scratch, and defines the control settings for specific property.

A control also provides pre-configured view prototypes for a grid column, a form field, and so on:

    /**
     * @property Grid\Column $grid_column #[Serialized]
     * @property Form\Field $form_field #[Serialized]
     */
    class Control extends Object_ {
    }      
    
### meta.abstract

I redesigned control rendering on list and edit pages. From now, a control contains view prototypes for a grid column, a form field, and other.
