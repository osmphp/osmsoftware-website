# Using Osm Framework With Other Frameworks

For quite some time, I create all new projects with Osm Framework. However, recently, I opened an old project, and wondered whether I can use Osm Framework there.

I mean, in theory, I can - the framework was created to be project-agnostic. In practice, I never did that, and most probably, it won't work smoothly out of the box.

Contents:

{{ toc }}

## PHP 8 Requirement

Before getting started, here is an important disclosure.

Osm Framework requires PHP 8, and it brings some limitations to using it with other frameworks.

Some frameworks, for example Magento 1, don't work with PHP 8 at all, and all you can do with Osm Framework is console commands and using them using `osm ...` command-line alias.

Other framework, for example Magento 2, support PHP 8 since a certain version, and code using Osm Framework won't work on earlier versions, except `osm ...` command-line alias.

## Sample Projects And Integration Packages

Let's see create some projects with frameworks I have worked earlier: Laravel, Symfony and Magento. 

At the very least, Osm Framework should be installed. 

In addition, an integration package should be installed (and created!) for each "host" framework. For example, `osmphp/laravel` will integrate Osm Framework into Laravel application.

We get this minimum amount of directories:

    osm/
        vendor/osmphp/
            core/
            framework/
    laravel/
        vendor/osmphp/
            core/
            framework/
            laravel/
    symfony/
        vendor/osmphp/
            core/
            framework/
            symfony/
    magento1/
        vendor/osmphp/
            core/
            framework/
            magento1/
    magento2/
        vendor/osmphp/
            core/
            framework/
            magento2/

