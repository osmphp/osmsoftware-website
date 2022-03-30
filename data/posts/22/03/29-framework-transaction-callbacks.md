# Transaction Callbacks 

While testing notification logic in Osm Admin, an exception is thrown in `$db->committing()` callback, and the logic in `$db->rollback()` fails. 

Let's review how transaction callbacks work, and fix it.

Contents:

{{ toc }}

### meta.abstract

While testing notification logic in Osm Admin, an exception is thrown in `$db->committing()` callback, and the logic in `$db->rollback()` fails.

Let's review how transaction callbacks work, and fix it.

## `committing()` AND `committed()` Callbacks

Use `committing()` method to register a callback that is executed before the transaction is committed. Osm Admin uses this method to create records in change notification tables.

Use `committed()` method to register a callback that is executed after the transaction is committed. Osm Admin uses this method to initiate incremental indexing.

Both these methods add callbacks to internal callback arrays.

Let's examine when the callbacks are executed.

First, keep in mind that inner transactions are just for better code structure. The actual transaction starts (and ends) only for the outer transaction. Internally, `Db` object keeps a transaction count. If it's 0, no transaction is running. If it's 1, the outer transaction is running. If it's greater than 1, an inner transaction is running:

    public function beginTransaction(): void {
        if ($this->transaction_count === 0) {
            $this->connection->beginTransaction();
        }

        $this->transaction_count++;
    }

When inner transaction is committed, the internal transaction count is decreased, and nothing else happens:

    public function commit(): void {
        $this->transaction_count--;
        if ($this->transaction_count > 0) {
            // in inner transaction, do nothing
            return;
        }
        ...
    }

When outer transaction is committed, `committing` callbacks are executed, and the actual transaction is committed:

    public function commit(): void {
        ...
        foreach ($this->committing as $callback) {
            $callback($this);
        }

        $this->connection->commit();
        ...
    }

After the actual transaction is committed, all callback arrays are cleared, and `committed` callbacks are executed:

    public function commit(): void {
        ...
        $committed = $this->committed;
        $this->committing = [];
        $this->committed = [];
        $this->rolled_back = [];

        foreach ($committed as $callback) {
            $callback($this);
        }
    }

## `rollback()` Is Executed Last

In most cases, use `transaction()` method:

    public function transaction(callable $callback): mixed {
        $this->beginTransaction();

        try {
            $result = $callback();
            $this->commit();
            return $result;
        }
        catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

In tests, use `dryRun()` method:

    public function dryRun(callable $callback): mixed {
        $this->beginTransaction();

        try {
            return $callback();
        }
        finally {
            $this->rollBack();
        }
    }

In both cases, a transaction is only rolled back at the last step. 

Alternatively, organize transactions using `beginTransaction()`, `commit()` and `rollback()` methods. Even then, only roll the transaction back at the very last step. 

As you'll see below, doing some logic after rolling an internal transaction back may produce unwanted side effects.

## `rolledBack()` Callbacks

Use `rolledBack()` method to register a callback that if an outer transaction fails, just after it's rolled back. 

In most cases, this kind of callback is not needed. However, if you modify the database *and* some external data storages, `rolledBack()` callbacks should undo any changes you made to data storages outside database.

**Example**. If you save an uploaded file on disk, register it in the database and the database transaction fails, provide a `rolledBack()` callback that deletes the uploaded file.  

Keep in mind that a transaction may be rolled back due to an error in a `committing()` callback. For every `committing()` callback that operates on data outside the database, provide a `rolledBack()` callback that undoes such changes. 

Let's examine when `rolled_back` callbacks are executed.

When rolling back an inner transaction, the transaction count is decreased, and nothing else happens:

    public function rollBack(): void {
        // if a `committing()` callback fails, the `transaction_count` may
        // be 0, not 1, so the check
        if ($this->transaction_count > 0) {
            $this->transaction_count--;
        }

        if ($this->transaction_count > 0) {
            // in inner transaction, do nothing
            return;
        }
        ...
    }

When an outer transaction is rolled back, the actual rollback occurs, all callback arrays are cleared, and `rolled_back` callbacks are executed:

    public function rollBack(): void {
        ...

        $rolledBack = array_reverse($this->rolled_back);
        $this->committing = [];
        $this->committed = [];
        $this->rolled_back = [];
        
        $this->connection->rollBack();

        foreach ($rolledBack as $callback) {
            $callback($this);
        }
    }

## Preventing Commit After Inner Rollback

As you can see, the inner rollback doesn't actually roll anything back, and trying to commit the outer transaction would result in committing changes that you expected to be rolled back.

Let's prevent it. I introduced a `rolling_transaction_back` property that becomes `true` after an inner transaction rollback, and is reset to `false` after transaction is over. It prevents starting or committing a transaction:

    public function beginTransaction(): void {
        if ($this->rolling_transaction_back) {
            throw new TransactionError(__(
                "Can't begin an inner transaction in the outer transaction that is being rolled back."));
        }
        ...
    }

    public function commit(): void {
        if ($this->rolling_transaction_back) {
            throw new TransactionError(__(
                "Can't commit a transaction that is being rolled back."));
        }
        ...
    }
    
## Preventing Inner Transactions In `committing()` Callbacks

`committing()` callbacks are always executed inside a transaction, and starting an inner transaction there would ruin the transaction state.

Let's prevent starting an inner transaction there. I introduced `committing_transaction` property that is `true` while `committing()` callbacks are executed, and it prevents starting an inner transaction:

    public function beginTransaction(): void {
        if ($this->committing_transaction) {
            throw new TransactionError(__(
                "Can't begin a transaction in a `committing()` callback"));
        }
        ...
    }
