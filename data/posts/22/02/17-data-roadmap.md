# Roadmap

My current goal is to get some minimum Osm Admin user interface and API working - just for one property, one data type, one UI control type, and then improve.

And there is a lot of space for improvement. The main areas are listed in this document. 

I'd really appreciate a helping hand, so if you are into this sort of things, [DM me on Twitter](https://twitter.com/v_osmianski).

Contents:

{{ toc }}

### meta.abstract

My current goal is to get some minimum Osm Admin user interface and API working - just for one property, one data type, one UI control type, and then improve.

And there is a lot of space for improvement. The main areas are listed in this document.

I'd really appreciate a helping hand, so if you are into this sort of things, *DM me on Twitter*.

## Schema Validation

It should be safe to write anything in a data class definition, and Osm Admin should provide meaningful error messages.

Currently, Osm Admin expects valid attributes and their combinations.

## Altering Existing Tables

It should be safe to change anything in a data class definition, and Osm Admin should modify existing table preserving existing data. Renaming a column, or changing a column type should work seamlessly.

Currently, altering existing tables is not supported.

## Data Types

SELECT, INSERT, UPDATE and DELETE should work with all data types, including objects, arrays, and record references.

Currently, only scalar values are supported.

## Input Validation

Validation should occur both in frontend, and in backend. In addition to property-level validation, the backend should perform object-level validation. For example, "order must have at least one line", "there can be only one root category".

Currently, there is input validation is not implemented.

## Property Formulas

You may specify a formula for computing a property value from other properties of the current and related objects. It can be:

* `#[Computed]` - stored in the database
* `#[Virtual]` - computed every time a SELECT query is performed
* `#[Overridable]` - stored in the database, but a user can enter a custom value

Property formulas should run in backend when "source" objects are modified, mostly asynchronously.

Property formulas should also run in the edit form, so if you edit a property, dependent property are recalculated. Form title should also change as you edit the `title` property.

Currently, property formulas are not implemented. 

## UI Controls

Scalars can be displayed as `#[Input]`, `#[Select]`, `#[Date]`, or `#[Switch]`.

Inner objects can be displayed as `#[File]`, `#[Image]`, or `#[Controls]` - a block of UI controls, one for each property of the inner object.

Record references can be displayed as `#[Select]` or `#[SelectGrid]`.

Arrays can be displayed, as `#[Multiselect]`, `#[MultiselectGrid]`, or `#[ChildGrid]`.  

These UI controls should work with all appropriate property types.

After implementing initial prototype, only `#[Input]` will be implemented, and only for working with `string` properties.

## Filtering, Sorting And Search

Properties may be marked as: 

* `#[Filterable]`, and appear in a filter block on grid and form pages, and in a grid column context menu, and in a grid context menu.   
* `#[Searchable]`, and added to an ElasticSearch index. User can full-text search objects of a specific type, or of any type.
* `#[Sortable]`, and appear in a grid column context menu, and in a grid context menu.   

The grid uses `ui_query()` function to orchestrate filtering, sorting and searching. If a search index exists, it should first query the search index, and then the database. If the search index is not available, it should perform filtering and sorting in the database and *get the same results*.

Currently, filtering, sorting and search are not implemented.

## Infinite Scrolling

A grid should be able to work with large amounts of data. 

It should appear as if it loads all matching objects, but actually, only load *visible* objects, and load the rest as user scrolls down/up.

After implementing initial prototype, grids will load all objects.

## Keyboard Navigation

A keyboard-only user should be as comfortable using Osm Admin as mouse/touch user. Navigating the page, invoking context menus, using keyboard shortcuts.

Currently, keyboard navigation is not implemented.

## Documentation And Tests

An easy-to-follow documentation should be written, and kept up to date.

Automated tests should have enough coverage to give users confidence to use Osm Admin, and kept up to date.

Currently, the documentation only contains some introduction, installation instruction, and class diagrams, and the test suites are mainly used for TDD.  


 
