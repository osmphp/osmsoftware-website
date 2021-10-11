# Project Structure

After you got a project up and running, let's have a look what's inside. Every directory has its purpose. Learn where to put your creative efforts!

**Note**. This post is moved to [Osm Framework documentation](https://osm.software/docs/framework/getting-started/project-structure.html).

Details:

{{ toc }}

## meta

    {
        "canonical_url": "https://osm.software/docs/framework/getting-started/project-structure.html"
    }

### meta.abstract

After you got a project up and running, let's have a look what's inside. Every directory has its purpose. Learn where to put your creative efforts!

**Note**. This post is moved to *Osm Framework documentation*.

## `.github/` - GitHub Settings

`workflows/deploy.yml_` defines a GitHub action that deploys project changes to the production server anytime a push is made to the GitHub repository, or a pull request merged into it. 

By default, it's disabled. In order to enable it, remove the last `_` character and configure it as specified [here](07-osmsoftware-deploying-updates.md).

## `bin/` - Shell Scripts

Use `deploy.sh` script on production server to [update the project from the GitHub repository](07-osmsoftware-deploying-updates.md#deployment-script).

Use `install.sh` script to [install the project](../08/10-framework-installation.md#creating-a-project).

## `generated/`

### `Osm_App/`

#### `app.ser`

#### `classes.php`

### `hints.php`

## `node_modules/`

## `public/`

### `Osm_App/`

#### `_front__tailwind/`

##### `scripts.js`

##### `styles.css`

##### `version.txt`

#### `index.php`

#### `router.php` 

## `samples/`

### `App.php`

## `src/`

### `Base/Module.php`

### `Welcome/`

#### `Routes/Front/Home.php`

#### `Module.php`

## `temp/`

### `Osm_App/`

## `tests/`

### `bootstrap.php`

### `test_01_hello.php`

## `themes/`

### `_front__tailwind/`

#### `views/welcome/home.blade.php`

#### `osm_theme.json`

## `vendor/`

## `composer.json`

## `composer.lock`

## `gulpfile.js`

## `LICENSE`

## `package.json`

## `package-lock.json`

## `phpunit.xml`

## `readme.md` 