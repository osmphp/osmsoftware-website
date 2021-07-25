# 2021 Jun 28 - Jul 09

Osm Framework introduced JS controllers, and JS unit tests. [osmcommerce.com](https://osm.software/) website project was renamed to [osm.software](https://osm.software/), it finalized the post rendering, and introduced a tool for checking broken links.  
 
{{ toc }}

## meta

    {
        "candidate_posts": [
            "osmsoftware-handling-images",
            "osmsoftware-handling-relative-links",
            "osmsoftware-checking-broken-links",
            "framework-writing-js-controllers",
            "framework-writing-js-classes",
            "framework-writing-js-unit-tests",
            "productivity-going-through-motivation-slump"
        ]
    }

### meta.list_text

Osm Framework introduced JS controllers, and JS unit tests. *osmcommerce.com*
website project was renamed to *osm.software*, it
finalized the post rendering, and introduced a tool for checking broken links.

## *osm.software* Website v0.1.2

Previous, now abandoned domain name is `osmcommerce.com`. 

[Diff](https://github.com/osmphp/osmsoftware-website/compare/v0.1.1...v0.1.2)

### Reporting Broken Links

There is a new command line utility, that reports all the broken links in the blog
source files:

    osm check:links
    
By default, it only reports relative blog links. 

If you want to check external, absolute links, add the `-x` option.

### Other changes

* rendering images
* transforming relative `.md` blog file links into valid URLs
* expanding `toc` tag
* rendering category and date links in the blog post header

### Fixes

* ordering items in the category filter
* scrolling to a sub-heading adjusted to a sticky header
* simplified display of applied filters    

## Osm Framework v0.9.0

[Diff](https://github.com/osmphp/framework/compare/v0.8.4...v0.9.0)

### JS Controllers

This version introduces the minimalistic JS framework for attaching JS objects 
responsible for HTML element behavior (called **controllers**) to HTML elements.

Let's illustrate it with an example. 

1. In the HTML markup, add `data-js-{controller_name}` attribute to an HTML element. In the example, controller name is `test`. In the attribute value, pass options JSON, or leave the attribute value empty. 
    
        <div id="test" data-js-test='{"param": "value"}'>
        </div>
 
2. Create a JS controller class, [TestController.js](https://github.com/osmphp/framework/blob/HEAD/themes/_front__tailwind_samples/js/sample-js/Controllers/TestController.js):

        import Controller from "../../js/Controller";
        import {register} from '../../js/scripts';
        
        export default register('test', class TestController extends Controller {
            clicked = false;
        
            get events() {
                return Object.assign({}, super.events, {
                    'click': 'onClicked',
                });
            }
        
            onClicked() {
                this.clicked = true;
            }
        });

3. Import the controller into the JS entry point of your module, [scripts.js](https://github.com/osmphp/framework/blob/HEAD/themes/_front__tailwind_samples/js/sample-js/scripts.js):

        import './Controllers/TestController';

4. Run `gulp`.

When the page loads, the controller object is attached to the HTML element, the options
provided in the attribute value are assigned to the `options` property of the controller, and the controller event handlers are registered with the DOM elements, too. 

See also [full unit test](https://github.com/osmphp/framework/blob/HEAD/themes/_front__tailwind_samples/files/sample-js/js.js).

### JS Unit Tests

In the framework project, there is a JS unit test runner page, `{base_url}/test/js?file=sample-js.js`. It runs tests defined in the [sample-js/js.js](https://github.com/osmphp/framework/blob/HEAD/themes/_front__tailwind_samples/files/sample-js/js.js) file.