# 2021 Aug 9 - Aug 27

New Osm Framework version comes with improved, configurable and easily customizable Gulp script, modular NPM dependencies, standard page layout Blade component and brand new `README`. [osm.software](https://osm.software/) website contains an easy-to-follow new project installation instruction.

More details:

{{ toc }}

### meta.abstract

New Osm Framework version comes with improved, configurable and easily
customizable Gulp script, modular NPM dependencies, standard page layout Blade
component and brand new `README`. *osm.software* website
contains an easy-to-follow new project installation instruction.

## Osm Framework v0.10.2

[Diff](https://github.com/osmphp/framework/compare/v0.9.3...v0.10.2)

### Customizable Gulp Configuration

From now on, Gulp tasks are defined inside Osm Framework. 

In the project's `gulpfile.js` file, define apps and themes to build/watch, and invoke the framework's Gulp script:

    // In the global configuration object, keys are application names to be
    // compiled, and values are arrays of theme names to build for that application
    global.config = {
        'Osm_Tools': [],
        'Osm_App': ['_front__tailwind']
    };
    
    // Run the framework Gulp scripts that define all the Gulp tasks, and
    // export these tasks to the Gulp runner
    Object.assign(exports, require('./vendor/osmphp/framework/gulp/main'));
 
Gulp compiles every listed app and clears its cache, as specified in the framework's `gulp/buildApp.js` file:

    module.exports = function buildApp(appName) {
        return series(
            compile(appName),
            refresh(appName),
        );
    }

After that, Gulp clears the `temp` directory of every theme, saves there a `config.json` file containing information about application modules and themes, collects theme files from the project and its dependencies into theme's `temp` directory, and, finally, calls theme-specific Gulp script. All these steps are specified in the framework's `gulp/buildTheme.js` file:

    module.exports = function buildTheme(appName, themeName) {
        let config = JSON.parse(
            execSync(`php ${osmt} config:gulp --app=${appName}`).toString());
    
        return series(
            clear(appName, themeName),
            save(appName, themeName, config),
            collect(appName, themeName, config),
            call(appName, themeName, config)
        );
    };

`collect()` respects theme inheritance. If `T2` theme extends `T1` theme, then Gulp collects all `T2` files, and all `T1` files that are not overridden by `T2`. 

Collected theme files include not only Blade templates, CSS styles, JS scripts and images, but also theme-specific Gulp scripts. Most theme-specific Gulp scripts are defined in the `_base` theme, see `themes/_base/gulp` directory. However, you can override any of these scripts in your theme. For example, `themes/_front__tailwind/gulp/css.js` adds Tailwind plugin to CSS file processing.

Typical theme-specific Gulp script clears theme's `public` directory, adds `styles.css` and `scripts.js` of application's modules, bumps the asset version (forcing browsers to reload all assets), builds and bundles CSS and JS, and copies image, font and other files that come with the application modules. 

    module.exports = function build() {
        return series(
            clear(),
            importJsModules(),
            importCssModules(),
            bump.once(),
            parallel(
                files('images'),
                files('fonts'),
                files('files'),
                js(),
                css()
            )
        );
    }

### Modular NPM Dependencies

Starting from this version, project's `package.json` file is merged from all module-specific `package.json` files found in the project and its dependencies. This allows module developers to bring their JS dependencies in the project.

Use the following command to collect the `package.json` file from all installed modules:

    osmt config:npm

### Standard Page Layout

Starting from this version, use standard page layout Blade component in your page templates:

    <x-std-pages::layout title='Welcome to Osm Framework'>
        <h1 class="text-2xl sm:text-4xl font-bold my-8">
            {{ \Osm\__("Welcome to Osm Framework") }}
        </h1>
    </x-base::layout>

### Other Changes

* Completely rewritten `README` 

## *osm.software* Website v0.2.2

[Diff](https://github.com/osmphp/osmsoftware-website/compare/v0.2.1...v0.2.2)

### New Content

New articles have been written, and previous articles have been edited and
revised:

* Osm Framework
    * [Installation](https://osm.software/blog/21/08/framework-installation.html) 
    * [Command Line Aliases](https://osm.software/blog/21/08/framework-command-line-aliases.html) 
* Meta (new category about how we work)
    * [GitHub Workflow](https://osm.software/blog/21/08/meta-github-workflow.html) 

### Changed License

The project license changed to AGPL according to updated approach to licensing: Osm Framework and other libraries are licensed under GPL while projects are licensed under AGPL.  

### Other Changes

* The website is migrated to the latest Osm Framework `^0.10`.
* Standard page layout component `std-pages::layout` is used instead of project-specific one.
* Gulp script is completely "inherited" from the framework.
* Git tracks `composer.lock` which allows to install the exact same dependencies in production with the `composer install` command. 

## Project Template v0.10.0

[Diff](https://github.com/osmphp/project/compare/v0.7...v0.10.0)

The project template has got a major rewrite. By default, a new project:

* includes a working "Welcome" home page
* uses all Osm Framework modules
* runs under native PHP Web server using new `router.php` file:

        php -S 0.0.0.0:8000 -t public/Osm_App public/Osm_App/router.php  

* uses the Gulp script that comes with Osm Framework
* stores `composer.lock` in Git which allows to install the exact same
  dependencies in production with the `composer install` command

## Osm Core v0.8.12

[Diff](https://github.com/osmphp/core/compare/v0.8.11...v0.8.12)

This version comes with a compiler fix. `Osm\Runtime\Compilation\CompiledApp` class has several properties listing its modules, a bit different from the previous version:

* `unsorted_modules` returns all modules found in the project and its dependencies.
* `referenced_modules` returns only those `unsorted_modules` that actually belong to the application, either directly specified in the module file, or in a module that depends on it.
* `modules` returns `referenced_modules` sorted by dependency.


