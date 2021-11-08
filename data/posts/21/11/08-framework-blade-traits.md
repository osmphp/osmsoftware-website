# Blade Traits

Currently, I'm working on Osm Admin package, and I need a module to inject its HTML markup around some well-known place in a Blade template. However, Blade template extensibility is not a problem that's specific to Osm Admin project. It's a generic problem. Let's solve that.

{{ toc }}

### meta.abstract

Currently, I'm working on Osm Admin package, and I need a module to inject its HTML markup around some well-known place in a Blade template. However, Blade template extensibility is not a problem that's specific to Osm Admin project. It's a generic problem. Let's solve that.

## Problem

New [`messages` module](https://github.com/osmphp/admin/blob/HEAD/src/Messages/Module.php) will provide JavaScript functions for showing automatically disappearing messages. In order to do that, it has to render a `<template>` tag in the end of HTML page during server-side page rendering, and then create HTML from it in JavaScript code.   

All page templates are based on `<x-std-pages::layout>` Blade component:

    <x-std-pages::layout>
        ...
    </x-std-pages::layout>

The `<x-std-pages::layout>` uses `std-pages::layout` template:

    <!doctype html>
    <html lang="en">
    ...
    <body>
        ...
        @if(isset($footer))
            {{ $footer }}
        @else
            @include('std-pages::footer')
        @endif
        ...
    </body>
    </html>
 
The `$footer` here is a Blade component slot that may be filled in by the caller template. Let's say that it will be rendered unconditionally:

    ...
    @include('std-pages::footer')
    {{ $footer }}
    ...

The `messages` module could render its `<template>` tag just after the `{{ footer }}` slot. However, currently there is no way for a module to inject its markup into an existing template.

## `@around` And `@proceed` Directives

A module can inject PHP code into any class method using a [dynamic trait](https://osm.software/docs/framework/writing-php-code/dynamic-traits.html). In a similar fashion, a module could inject its markup use a kind of *Blade template trait*. 

Let's say that the `messages` module defines a *Blade template trait* in `themes/_base/views/messages/traits/std-pages/layout.blade.php`:

    @around({{ $footer }})
        @proceed
        <template id="message-template">
            ...
        </template> 
    @endaround
     
The file path specifies that it's a trait by being in the `traits/` subdirectory, and that it extends the `std-pages::layout` template.

New `@around` directive specifies a text to search - `{{ $footer }}`, and a text to replace it with - everything till `@endaround`.
 
New `@proceed` directive specifies the position of the injected markup relative to the original slot content. In this example, the injected `<template>` tag is injected *after* the original content.

This way, any module could inject their content before and after the original content.

The same file can contain several `@around` directives. If `@around` directive goes without parameters, the whole template is replaced:

    @around
        @proceed
        <!-- Everything here goes after closing `</html>` tag -->
    @endaround

## Implementation

Said and done!

I had to dig into how Blade actually works. Before rendering every template, it compiles it into plain PHP template using `Compiler::compileString()` method. 

Luckily, I already subclassed the `Compiler` class earlier, so I overwrote the `compileString()` method. The overwritten method checks if any module has a matching file in `views/{module}/traits/{path}` directory, and if so, it reads and applies all the `@around` directives to the currently compiled Blade template.

Full implementation is 50-60 lines of code, a lot less than I expected! See [`Compiler`](https://github.com/osmphp/framework/blob/HEAD/src/Blade/Compiler.php) class for more details.  



