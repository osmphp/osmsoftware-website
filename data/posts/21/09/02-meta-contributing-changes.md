# Contributing Changes

This article provides a practical example of contributing changes to Osm Framework and other `osmphp/*` GitHub repositories.

Contents:

{{ toc }}

## meta.list_text

This article provides a practical example of contributing changes to Osm Framework
and other `osmphp/*` GitHub repositories.

## Sample Contribution

Before diving into the contribution process, let's review what we are going to contribute. 

### Problem

A typical Osm Framework-based project contains several applications:

* `Osm_App` is the main application that you eventually host on the Web.
* `My_Samples` is a superset of `Osm_App` used in unit tests. It may contain additional modules that are only used in tests.
* `Osm_Tools` is an auxiliary application used to develop and manage the main application.

Each application has intimate knowledge about its modules and classes.

However, in some cases, it's useful to introspect all modules and classes of the project regardless to which application they belong. It is especially useful in code generation.

### Solution

*It's written in present tense, as if the change is already implemented, on purpose. It makes it easier to update the framework documentation with new features*.

New `Osm_Project` application allows to introspect all modules and classes of
the project regardless to which application they belong. 

Use it as follows:

    use Osm\Project\App;
    ...
    
    $result = [];
    
    Apps::run(Apps::create(App::class), function (App $app) use (&$result) {
        // use $app->modules and $app->classes to introspect 
        // all modules and classes of the projects and its 
        // installed dependencies
        
        // put the introspection data you need into `$result`. Don't reference
        // objects of the `Osm_Project` application, as they'll won't work 
        // outside this closure. Instead, create plain PHP objects and arrays, 
        // and fill them as needed 
    });

**Notice**. This application is for introspection only. Invoking user code in context of this application may produce unexpected results.

### How It Works

Internally, `Osm_Project` app sets new `load_all` flag:

    class App extends BaseApp
    {
        public static bool $load_all = true;
    }

The compiler checks this flag and doesn't unload unreferenced modules:

    protected function get_referenced_modules(): array {
        return $this->load_all
            ? $this->unsorted_modules
            : $this->unloadUnreferencedModules($this->unsorted_modules);
    }

    protected function get_load_all(): bool {
        $class = $this->class_name; /* @var App $class */

        return $class::$load_all;
    }

### Related Contributions

It's a major change, all projects should compile the `Osm_Project` app in
the `gulpfile.js`:

    global.config = {
        'Osm_Tools': [],
        'Osm_Project': [], // NEW
        'Osm_App': ['_front__tailwind']
    };

You should normally contribute this change to the `osmphp/project` project template, and add upgrade notice to the `osmphp/osmsoftware-website` documentation
repository. The contribution process is the same, so I'll omit it for brevity.

## Rules

Send bug fixes, and minor features to the current release branch. Prefix commit
messages with `fix:` and `minor:`, respectively:

![GitHub Current Branch](github-current-branch.png)

Send major new features should always be sent to the upcoming release branch (
for `v0.10` current release, it's `v0.11`; for `v2`, it's `v3`, and so on).
Prefix commit messages with `major:`.

In case the upcoming release branch doesn't exist yet, ask maintainer to create it in repository Discussions.

## Workflow

1. Use "Fork" button to fork [the relevant repository](https://github.com/osmphp) (in our example, it's `osmphp/core`) into your account (in my case, `osmianski`).

2. Clone the repository to your computer using `git clone` command:

        git clone git@github.com:osmianski/core.git
 
3. Implement changes. Write unit tests in order to make sure that future changes made by other contributors won't break yours.  

4. Commit and push the changes to your fork:

        git add .
        git commit -am "major: new `Osm_Project` application"
        git push
        
5. Create new pull request in the original repository, click `compare across
   forks`. In `base repository`, pick [current or upcoming branch](#rules) of the original repository. In `head repository`, pick the branch you made changes on. Click `Create pull request`, enter description, and click `Create pull request` again. See also [Creating a pull request from a fork](https://docs.github.com/en/github/collaborating-with-pull-requests/proposing-changes-to-your-work-with-pull-requests/creating-a-pull-request-from-a-fork).
   
## Using Fork In Your Project

While maintainer reviews and merges your pull request, you can use your fork instead of original repository in your project. Instruct Composer to use the fork, and your branch in the project's `composer.json`, and run `composer update`:

    {
        ...
        "require": {
            ...
            "osmphp/core": "v0.8.x-dev"
        },
        ...
        "repositories": [
            {
                "type": "vcs",
                "url": "https://github.com/osmianski/core"
            }
        ]
    }
 
**Note**. `v0.8.x-dev` version constraint in `composer.json` stands for `v0.8` Git branch. For more information, read about [Composer branch constraints](https://getcomposer.org/doc/articles/versions.md#branches).      

