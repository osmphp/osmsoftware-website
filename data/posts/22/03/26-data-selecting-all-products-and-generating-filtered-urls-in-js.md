# Selecting All Products And Generating Filtered URLs In JS

I've got a little more organized, then fixed an error in selecting all products, and implemented filtered URL generation in the browser.

Contents:
 
{{ toc }}

### meta.abstract

I've got a little more organized, then fixed an error in selecting all products, and implemented filtered URL generation in the browser.

## So Many Things To Do

Something has changed.

As I get closer to something that works, bugs , minor improvements, and thoughts about next big features keep popping at ever-increasing rate.

My current way of working - picking the next most relevant thing to do, and doing it - gets stuck. 

It's still important to focus on one thing at a time, but I'm going to do 2 changes in how I work:

1. I'll dump all things to do into a not-too-serious backlog in Trello. Every item there is not a commitment, but rather a reminder that something needs looking at. The goal here is to move things that I'll not do right now out of my focus.
2. I'll do quick fixes in batches, without taking breaks in between, starting with two fixes in a batch, and eventually increasing the batch size.

## JS Error While Selecting All Products

The checkbox in the handle column header should select all/deselect all products, but it throws an error:

    Uncaught TypeError: Cannot set properties of undefined (setting 'inverse_selection')
    at Handle.onClick
    
What's going on? I assigned the `grid_` CSS class that marks the grid HTML fragment to wrong HTML element. Fixed.

## Generating Filtered URLs In JS

How it should work:

* After selecting all products and pressing the `Edit` button, it should open `GET /products/edit?all`.
* If some products are deselected, it should open `GET /products/edit?all` 
* If a filter is applied, for example, `?color=red`, it should keep the filter in the edit page URL: `GET /products/edit?color=red`.

**Note**. If you wonder what the URL parameters mean, and why they are written this way, check [the blog post on filter URL syntax](../01/10-data-filters.md#filter-syntax). 

Currently, it doesn't work this way. The logic that adds filters to the `Edit` button link, inherited from `v0.1` is quite naive:

    // themes/_admin__tailwind/js/ui/Controllers/Grid.js
    filterUrl(url) {
        if (this._inverse_selection) {
            const ids = this.ids(false);
            return url + (ids.length
                ? `?id-=${this.ids(false).join('+')}`
                : '?all'
            );
        }
        else {
            return url + `?id=${this.ids(true).join('+')}`;
        }
    }

Hmm, it seems that grid JS controller options are not documented yet, let's do it:

    /**
     * @property {boolean} options.s_selected Text says how many objects are
     *      currently selected.
     * @property {int} options.count Number of matching objects.
     * @property {string} options.edit_url Edit page URL, without filter parameters
     * @property {string} options.delete_url Delete route URL, without
     *      filter parameters
     * @property {string} options.s_deleting Message that shows up while selected
     *      objects are being deleted
     * @property {string} options.s_deleted Message informing that the selected
     *      objects have been successfully deleted
     */
    export default register('grid', class Grid extends Controller {
        ...
    });

**Note**. I introduced the good practice of documenting the options a JS controller expects to receive from the HTML markup [while developing the field control behavior](23-data-field-control-behavior.md#controller-options).    

Back to `Grid.filterUrl()` JS method. 

It should work the same way the `Ui\Query::toUrl()` work on the server side. It means that it should know currently applied URL parameters, receive URL actions that change these parameters, and generate the URL accordingly.

**Note**. URL action syntax (`-`, `-color`, `-color=red`, `color=red` and `+color=red`) is introduced while implementing [facet rendering](16-data-rendering-facets-and-applying-filters.md#action-syntax).

The first thing is to pass `Ui\Query::$url_parameters` to the browser:

    // Osm\Admin\Ui\List_\Grid
    protected function get_data(): array {
        return [
            ...
            'js' => [
                ...
                'url_parameters' => (object)$this->query->url_parameters,
            ],
        ];
    }


If there are no filters, it will be an empty object. If there is a color filter applied, it will contain:

    {
        "color": ["pink", "blue"]
    }

Then, let's generate filtered URL in the browser using new `Grid.toUrl()` method:

    let url = this.toUrl(this.options.edit_url, true, this.idFilter());

It accepts three parameters: an absolute unfiltered URL, a `safe` flag that adds `?all` if there are no other filters, and an array of URL actions. This method take a copy of `url_parameters` received from the server, applies URL actions to it, and converts it to an absolute filtered URL:

    toUrl(url, safe, actions) {
        let parameters = JSON.parse(
            JSON.stringify(this.options.url_parameters || {}));

        actions.forEach(action => {
            this.applyUrlAction(parameters, action);
        });

        if (safe && !this.urlHasFilters(parameters)) {
            parameters.all = true;
        }

        const urlParameters = this.renderUrlParameters(parameters);

        return urlParameters.length ? `${url}?${urlParameters}` : url;
    }

`Grid.idFilter()` method returns URL actions that clear `id` filter, and then add selected row IDs to it:

    idFilter() {
        const ids = this.ids(!this._inverse_selection).join(' ');

        let urlActions = ['-id', '-id-'];
        if (!ids.length) {
            return urlActions;
        }

        urlActions.push(this._inverse_selection ? `id-=${ids}` : `id=${ids}`);

        return urlActions;
    }

