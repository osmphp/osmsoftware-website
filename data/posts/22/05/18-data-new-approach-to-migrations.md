# New Approach To Migrations

I continued working on property diff algorithm that plans all the migration details.

Contents:

{{ toc }}

### meta.abstract

I continued working on property diff algorithm that plans all the migration details.

## Main Idea

The refactoring idea is to:

1. Compare property attributes in `Property::diff()`, and to register various callbacks along the way. 
2. During migration and  conversion, execute all registered callbacks.

## `Property::diff()`

Again, just as previous `Migration` classes, the `Property::diff()` method compares individual property attributes: 

    // Osm\Admin\Schema\Diff\Property\Int_
    public function diff(): void {
        $this->explicit();
        $this->type();
        $this->nullable();
        $this->size();
        $this->unsigned();
        $this->autoIncrement();
    }

    // Osm\Admin\Schema\Diff\Property\String_
    public function diff(): void {
        $this->explicit();
        $this->type();
        $this->nullable();
        $this->size();
        $this->length();
    }

## `Property::explicit()`

This method checks if the property is brand new, if it becomes explicit, or, on the contrary, if it becomes implicit and "orders" needed migrations and data conversions: 

    protected function explicit(): void {
        $this->attribute('explicit', function() {
            if ($this->new->explicit) {
                if ($this->old) {
                    if (!$this->old->explicit) {
                        $this->createColumn();
                        $this->convert();
                        $this->dropJson();
                    }
                }
                else {
                    $this->createColumn();
                }
                
            }
            else { // !$this->new->explicit
                if ($this->old?->explicit) {
                    $this->convert();
                    $this->dropColumn();
                }
            }
        });
    }

The `createColumn()`, `convert()` and other helper methods used here basically just set some boolean flags  and register callbacks to be processed later during the migration phase. 


