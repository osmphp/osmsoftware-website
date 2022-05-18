# Finishing Data Conversions

Yesterday, I finished implementing data conversion for `int` and `string` property types.

Then, I started refactoring it. 

Contents:

{{ toc }}

### meta.abstract

Yesterday, I finished implementing data conversion for `int` and `string` property types.

Then, I started refactoring it.

## Implementing Missing Formula Functions And Constructs

### `COLUMN()` Function

The data conversion uses the new `COLUMN()` formula function that's not implemented yet.

Here is the implementation:

    #[Type('column')]
    class Column extends Function_
    {
        public function resolve(Formula\Call $call, Table $table): void {
            if (count($call->args) !== 2) {
                throw new InvalidCall(
                    __("Pass 2 arguments to the ':function' function", [
                        'function' => strtoupper($call->type),
                    ]),
                    $call->formula, $call->pos, $call->length);
            }
    
            /* @var Formula\Literal $columnNameExpr */
            $columnNameExpr = $call->args[0];
            /* @var Formula\Literal $dataTypeExpr */
            $dataTypeExpr = $call->args[1];
    
            if ($columnNameExpr->type !== Formula::LITERAL ||
                $columnNameExpr->data_type->type !== 'string')
            {
                throw new InvalidCall(
                    __("Pass a string literal to the 1-st argument of the ':function' function", [
                        'function' => strtoupper($call->type),
                    ]),
                    $call->formula, $call->pos, $call->length);
            }
    
            $columnName = $columnNameExpr->value();
            if (!preg_match('/^[_a-zA-Z]\w*$/', $columnName)) {
                throw new InvalidCall(
                    __("Pass a valid column name to the 1-st argument of the ':function' function", [
                        'function' => strtoupper($call->type),
                    ]),
                    $call->formula, $call->pos, $call->length);
            }
    
            if ($dataTypeExpr->type !== Formula::LITERAL ||
                $dataTypeExpr->data_type->type !== 'string')
            {
                throw new InvalidCall(
                    __("Pass a string literal to the 2-nd argument of the ':function' function", [
                        'function' => strtoupper($call->type),
                    ]),
                    $call->formula, $call->pos, $call->length);
            }
    
            $dataType = $dataTypeExpr->value();
            if (!isset($this->data_types[$dataType])) {
                throw new InvalidCall(
                    __("Pass a valid data type name to the 2-nd argument of the ':function' function", [
                        'function' => strtoupper($call->type),
                    ]),
                    $call->formula, $call->pos, $call->length);
            }
    
            $call->column_name = $columnName;
            $call->data_type = $this->data_types[$dataType];
            $call->array = false;
            $call->alias = $table->table_name;
        }
    
        public function toSql(Formula\Call $call, array &$bindings,
            array &$from, string $join): string
        {
            return "`{$call->alias}`.`{$call->column_name}`";
        }
    }
    
**Later**. All the argument checks are good candidates for extraction into reusable helper methods.

### `Migration::changeTypeByDbMeans()`

One more unimplemented piece - a column migration should decide whether it trusts native MySql migration, or, if not, if it requires data migration to be run.

I've already described this logic before:

* converting from any type to `string` trusts MySql
* converting to any other type runs data conversion

Implementation:

    // Osm\Admin\Schema\Diff\Migration
    protected function changeTypeByDbMeans(): bool {
        return false;
    }
    
    // Osm\Admin\Schema\Diff\Migration\String_
    protected function changeTypeByDbMeans(): bool {
        return true;
    }
    
**Later**. Keeping separate methods allows to fine-tune the logic if needed.

### Ternary Operator

In SQL formulas, `expr1 ? expr2 : expr3` is used instead of MySql `IF()` function.

It's used in data conversions, so let's implement it:

    class Ternary extends Formula
    {
        ...    
        public function resolve(Table $table): void
        {
            $this->condition->resolve($table);
            $this->then->resolve($table);
            $this->else_->resolve($table);
    
            $this->condition = $this->condition->castTo('bool');
            if ($this->then->data_type !== $this->else_->data_type) {
                $this->else_ = $this->else_->castTo($this->then->data_type->type);
            }
        }
    
        public function toSql(array &$bindings, array &$from, string $join): string
        {
            return "IF({$this->condition->toSql($bindings, $from, $join)}, ".
                "{$this->then->args[1]->toSql($bindings, $from, $join)}, " .
                "{$this->else_->toSql($bindings, $from, $join)})";
        }
    }
    
### `LENGTH()` Function

    #[Type('length')]
    class Length extends Function_
    {
        public function resolve(Formula\Call $call, Table $table): void {
            $this->argCountIs($call, 1);
            $call->args[0] = $call->args[0]->castTo('string');
             
            $call->data_type = $this->data_types['int'];
            $call->array = false;
        }
    
        public function toSql(Formula\Call $call, array &$bindings,
            array &$from, string $join): string
        {
            return "LENGTH({$call->args[0]->toSql($bindings, $from, $join)})";
        }
    }
    
**Notice**. The `argCountIs()` helper method is refactored from the argument count check I've implemented in the `COLUMN()` function.

### `LEFT()` Function

    #[Type('left')]
    class Left extends Function_
    {
        public function resolve(Formula\Call $call, Table $table): void {
            $this->argCountIs($call, 2);
            $call->args[0] = $call->args[0]->castTo('string');
            $call->args[1] = $call->args[1]->castTo('int');
    
            $call->data_type = $this->data_types['string'];
            $call->array = false;
        }
    
        public function toSql(Formula\Call $call, array &$bindings,
            array &$from, string $join): string
        {
            return "LEFT({$this->argsToSql($call, $bindings, $from, $join)})";
        }
    }
    
**Notice**. Transposing all function arguments to SQL is done in `argsToSql()` helper method:

    protected function argsToSql(Formula\Call $call, array &$bindings,
        array &$from, string $join): string 
    {
        $sql = '';
        
        foreach ($call->args as $arg) {
            if ($sql) {
                $sql .= ', ';
            }
            
            $sql .= $arg->toSql($bindings, $from, $join);
        }
        
        return $sql;
    }

## Need For Refactoring

Consider the current `int` size change logic:

    protected function size(): void {
        $this->logAttribute('size');

        if (!$this->property->old) {
            $this->preSize();
            return;
        }

        if ($this->property->old->size === $this->property->new->size) {
            return;
        }

        if ($this->becomingSmaller()) {
            $this->checkRange('size');
            $this->postSize();
        }
        else {
            $this->preSize();
        }
    }

This method works as follows:

* If the property is new, the column is resized in the creation or pre-alter phase.
* Otherwise: 
    * If size doesn't change, size if not added to property definition at all.
    * If size decreases, resize data conversion is requested and the column is resized in the post-alter phase.
    * If size increases, the column is resized in the pre-alter phase.  

This logic doesn't stack up well with other attribute changes. For example, if property type changes from `string`, data conversion should be done either way, and property should be migrated in the post-alter phase.

It means that all methods handling individual attributes should be re-thought. 

The key question these methods should answer is whether data conversion is required. If so, then what DDL changes should be made before migration, and what DDL changes should be made after migration. If no, what DDL changes should be made.

In a word, a refactoring is needed. 

