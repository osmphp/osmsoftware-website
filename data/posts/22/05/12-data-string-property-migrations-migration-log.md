# `string` Property Migrations. Migration Log

Yesterday: 

* I finished implementing `string` property migrations.
* Migrations have got a meaningful log explaining what migrations take place, and why.
* Along the way, I fixed numerous issues.

In detail:

{{ toc }}

### meta.abstract

* I finished implementing `string` property migrations.
* Migrations have got a meaningful log explaining what migrations take place, and why.
* Along the way, I fixed numerous issues.

## Section IDs In Blog Posts

One of the objectives of this blog (see [its source code](https://github.com/osmphp/osmsoftware-website)) that it should be equally browsable both in a Web browser and on GitHub. For this reason, all internal links are written in a GitHub way, and during rendering they are converted to absolute links that works in a browser.

However, I've noticed that section IDs are not fully compatible with GitHub. For example, for the `Migration\Int_::size()` heading, the blog generates `#migration-int_-size` while GitHub expects `#migrationint_size`.

Let's fix that.  

The faulty code:

    // Osm\Data\Markdown\File
    protected function generateId(string $heading): string {
        $id = mb_strtolower($heading);

        $id = preg_replace('/[^\w\d\- ]+/u', ' ', $id);
        $id = preg_replace('/\s+/u', '-', $id);
        $id = preg_replace('/^\-+/u', '', $id);
        $id = preg_replace('/\-+$/u', '', $id);

        return $id;
    }

Here is the fix:

    protected function generateId(string $heading): string {
        $id = mb_strtolower($heading);

        $id = preg_replace('/[^\w\d\s]+/u', '', $id);
        $id = preg_replace('/\s+/u', '-', $id);
        $id = preg_replace('/^\-+/u', '', $id);
        $id = preg_replace('/\-+$/u', '', $id);

        return $id;
    }
 
## `string` Property Migrations

### `Migration\String_::size()`

In a sense, `String_::$size` is similar to [`Int_::$size`](11-data-int-property-migrations.md#migrationint_size).

If `TEXT` column becomes `TINYTEXT`, all values longer than maximum allowed length (for `TINYTEXT`, it's [`85` characters](https://stackoverflow.com/questions/13932750/tinytext-text-mediumtext-and-longtext-maximum-storage-sizes)) should be shortened.

On the contrary, if the maximum length increases, no shortening is necessary.

If conversion is needed, pre-alter phase should not change the column definition.        

**Notice**. If `#[Length]` attribute is used, `VARCHAR` is used instead, and all of this `TEXT` logic is not applied.

It should be possible to invoke text shortening multiple times as it's needed in other cases, for example, if `max_length` changes.

The implementation:

    protected function size(): void {
        if ($this->property->new->max_length) {
            return;
        }

        if (!$this->property->old) {
            $this->preSize();
            return;
        }

        if ($this->property->old->size === $this->property->new->size) {
            return;
        }

        if ($this->becomingShorter()) {
            $this->truncate();
            $this->postSize();
        }
        else {
            $this->preSize();
        }
    }

    protected function preSize(): void {
        if ($this->mode == Property::CREATE ||
            $this->mode == Property::PRE_ALTER)
        {
            $this->setSize();
        }
    }

    protected function postSize(): void {
        if ($this->mode == Property::POST_ALTER) {
            $this->setSize();
        }
    }

    protected function setSize(): void {
        if ($this->column) {
            $this->column->type(
                $this->sizes[$this->property->new->size]->sql_type);
            $this->run = true;
        }
    }

    protected function becomingShorter(): bool {
        return $this->maxLength($this->property->old) >
            $this->maxLength($this->property->new);
    }

    protected function maxLength(
        PropertyObject\String_|\stdClass|null $property): int
    {
        if (!$property) {
            return 0;
        }

        if ($property->max_length) {
            return $property->max_length;
        }

        return $this->sizes[$property->size]->max_length;
    }

    protected function truncate(): void {
        if ($this->truncate) {
            return;
        }

        $this->truncate = true;

        if ($this->mode === Property::CONVERT) {
            $maxLength = $this->maxLength($this->property->new);

            $this->new_value =
                "IF(LENGTH({$this->new_value} ?? '') > $maxLength, " .
                "LEFT({$this->new_value}, $maxLength), {$this->new_value})";
            $this->run = true;
        }
    }

### `Migration\String_::length()`

Handling of `String_::$max_length` is similar to `String_::$size`:

    protected function length(): void {
        if (!$this->property->new->max_length) {
            return;
        }

        if (!$this->property->old) {
            $this->preLength();
            return;
        }

        if ($this->property->old->max_length === 
            $this->property->new->max_length) 
        {
            return;
        }

        if ($this->becomingShorter()) {
            $this->truncate();
            $this->postLength();
        }
        else {
            $this->preLength();
        }
    }

    protected function preLength(): void {
        if ($this->mode == Property::CREATE ||
            $this->mode == Property::PRE_ALTER)
        {
            $this->setSize();
        }
    }

    protected function postLength(): void {
        if ($this->mode == Property::POST_ALTER) {
            $this->setLength();
        }
    }

    protected function setLength(): void {
        if ($this->column) {
            $this->column->type('string');
            
            /** @noinspection PhpUndefinedMethodInspection */
            $this->column->length($this->property->new->max_length);
            
            $this->run = true;
        }
    }

## Migration Log

After writing lots of untested code, it's time to test it, and to do it effectively, I need the [migration log](09-data-changing-property-type.md#migration-log-example) that explains what migrations take place, and why.

### Created/Altered Columns

Adding logging:

    // Osm\Admin\Schema\Diff\Migration::init()
    if ($this->table) {
        if ($this->property->old) {
            $this->logProperty(__("Altering property ':property'", [
                'property' => $this->property->new->name,
            ]));
        }
        else {
            $this->logProperty(__("Creating property ':property'", [
                'property' => $this->property->new->name,
            ]));
        }
    }

Here is the output:

    Migrating 'Osm\Admin\Samples\Migrations\String_\V001\' schema fixture 
    Creating 'products' table 
        Creating property 'id' 
        Creating property 'title' 
        Creating system columns: '_data', `_overrides` 
    --------------------------------------------- 
    Migrating 'Osm\Admin\Samples\Migrations\String_\V002\' schema fixture 
    Pre-altering 'products' table 
        Altering property 'id' 
        Altering property 'title' 
        Creating property 'description' 
                    
It's interesting why `id` column is being altered - it shouldn't. Logging column attributes should help.

### Column Attributes

Adding logging:

    protected function explicit(): void {
        if ($this->table) {
            $this->logAttribute('explicit');
        }
        ...
    }

    protected function logAttribute(string $attr): void {
        if ($this->property->old) {
            if ($this->property->old->$attr ?? null ===
                $this->property->new->$attr)
            {
                return;
            }

            $message = __(":attribute: :old => :new", [
                'attribute' => $attr,
                'old' => var_export($this->property->old->$attr ?? null,
                    true),
                'new' => var_export($this->property->old->$attr, true),
            ]);
        }
        else {
            $message = __(":attribute: :new", [
                'attribute' => $attr,
                'new' => var_export($this->property->new->$attr, true),
            ]);
        }

        $this->log->notice('        ' . $message);
    }

Output:

    Migrating 'Osm\Admin\Samples\Migrations\String_\V001\' schema fixture 
    Creating 'products' table 
        Creating property 'id' 
            explicit: true 
            type: 'int' 
            nullable: false 
            size: 'medium' 
            unsigned: true 
            auto_increment: true 
        Creating property 'title' 
            explicit: false 
            type: 'string' 
            nullable: true 
            size: 'small' 
            max_length: NULL 
        Creating system columns: '_data', `_overrides` 
    --------------------------------------------- 
    Migrating 'Osm\Admin\Samples\Migrations\String_\V002\' schema fixture 
    Pre-altering 'products' table 
        Altering property 'id' 
        Altering property 'title' 
        Creating property 'description' 
            explicit: true 
            type: 'string' 
            nullable: true 
            size: 'small' 
            max_length: NULL 
            
The log still doesn't answer why `id` is being altered.

### `$this->run = true`

To catch source lines triggering the column change, I replaced all `$this->run = true` lines with calls to new `run()` method:

    protected function run(string $attr): void {
        $this->run = true;

        if ($this->table && $this->property->old) {
            $this->log->notice("        !{$attr}");
        }
    }

And now I see what attributes trigger the column change:

    Migrating 'Osm\Admin\Samples\Migrations\String_\V002\' schema fixture 
    Pre-altering 'products' table 
        Altering property 'id' 
            !nullable 
            !auto_increment 
        Altering property 'title' 
            !nullable [] [] 
        Creating property 'description' 
            ...
            
## Fixing `nullable` And `auto_increment` Issues

Now, there are two issues with `nullable`:

1. When changing `unsigned int` column, it becomes `signed`.
2. It triggers unnecessary column change.

### Unintended Unsigned => Signed

Fix:

    protected function unsigned(): void {
        ...
        $this->preOldUnsigned();
        ...
    }

    protected function preOldUnsigned(): void {
        if ($this->mode == Property::CREATE ||
            $this->mode == Property::PRE_ALTER)
        {
            if ($this->column && $this->property->old->explicit) {
                if ($this->property->old->actually_unsigned) {
                    $this->column->unsigned();
                }
            }
        }
    }

**Later**. Maybe other column attributes should also be set in the pre-alter phase.

### Unnecessary Column Change

To fix it, I wrapped `run()` into an additional check if the `nullable` attribute has actually changed:

    if ($this->property->new->actually_nullable !==
        $this->property->old?->actually_nullable)
    {
        $this->run('nullable');
    }
 

