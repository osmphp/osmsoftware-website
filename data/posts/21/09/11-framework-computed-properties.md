# Computed Properties

***DRAFT***

Contents:

{{ toc }}

## meta.list_text

***DRAFT***

## Example

Let's start with an example. 

Consider a class that reads a Markdown file and transforms into HTML:

	/**
	 * @property string $path Relative file path in the `data` directory. 
	 *      Provide this property in the constructor.
	 * @property string $absolute_path Absolute file path
	 *
	 * @property string $text Original text in Markdown format
	 * @property string $html Text converted to HTML
	 */
	class MarkdownFile extends Object_ {
	    protected function get_absolute_path(): string {
	        // get the reference to the global application object which,
	        // among other things, stores the absolute path of the `data`
	        // directory in its `paths->data` property 
	        global $osm_app; /* @var App $osm_app */
	
	        return "{$osm_app->paths->data}/posts/{$this->path}";
	    }
	
	    protected function get_text(): string {
	        return file_get_contents($this->absolute_path);
	    }
	
	    protected function get_html(): ?string {
	        // convert the text into HTML using `michelf/php-markdown` 
	        // Composer package
	        return MarkdownExtra::defaultTransform($this->text);
	    }
	} 

The class is used as follows:

	// `MarkdownFile::new()` creates new instance of the class, 
	// just as `new MarkdownFile()` would do, plus it applies dynamic traits
	$file = MarkdownFile::new(['path' => 'welcome.md']);
	
	echo $file->html;

In the example above, `path`, `absolute_path`, `text` and `html` are *computed properties*. 

`path` property is assigned in the constructor.

`absolute_path`, `text` and `html` properties are computed in the `get_absolute_path()`, `get_text()`, and `get_html()` methods (known as *computed property getters*), respectively. 

## How The Example Actually Works

Let's examine what happens when the following files are executed:

	$file = MarkdownFile::new(['path' => 'welcome.md']);
	echo $file->html;
 
The first line creates an instance of `MarkdownFile` class, and assigns a value to the `path` property. The rest properties are not assigned, they don't even exist yet.

The second line tries to access `html` property, but it doesn't exist! Hence, it calls the getter, `get_html()` method: 

    protected function get_html(): ?string {
        return MarkdownExtra::defaultTransform($this->text);
    }

This method retrieves value of the `text` property, but it doesn't exist either! Again the property getter is called:

    protected function get_text(): string {
        return file_get_contents($this->absolute_path);
    }

This getter refers to yet another non-existent property, `absolute_path`, and again, the getter is called:

    protected function get_absolute_path(): string {
        global $osm_app; /* @var App $osm_app */
        return "{$osm_app->paths->data}/posts/{$this->path}";
    }

Finally, this getter accesses the `path` property that exists and is assigned, so it creates the `absolute_path` property and assigns the computed value to it.

Back in the `get_text()` method, the computed value of the `absolute_path` property is successfully used. The `text` property is created and assigned. 

The same happens in the `get_html()` method.  

## Properties Are Computed Only Once

Computed property getters are only executed on first access. The computed value is stored in the object, and on subsequent access, the stored value is used.

It works in similar fashion as the code below, but faster:

	protected string $absolutePath = null;

	public function getAbsolutePath(): string {
		if ($this->absolutePath === null) {
			$this->absolutePath = ...;
		}
	
		return $this->absolutePath;
	} 

It's also worth mentioning that if a computed property is not used, its getter is not called at all.

## Computed Properties Are Read-Only

Don't directly assign a value to a computed property like this:

	// bad idea!
	file->text = 'foo';

This may lead to code that is hard to maintain. 

However, in case you assigned a value to a computed property accidentally, use. In thiIn order to find a line that  

## Assigning Properties In Constructor

## Throwing An Exception If Property Is Not Assigned

## Serializing Properties

## Cached Properties

## Testing Computed Properties

## Defining Class Dependencies