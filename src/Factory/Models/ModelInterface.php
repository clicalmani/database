<?php
namespace Clicalmani\Database\Factory\Models;

use Clicalmani\Foundation\Collection\CollectionInterface;

interface ModelInterface extends SQLClausesInterface, SQLCasesInterface, RelationshipsInterface, Joinable, SQLAggregateInterface, EventInterface, StateChangeInterface
{
    /**
     * Get the query results.
     * 
     * @param string $fields SQL select statement.
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     * @throws \Clicalmani\Database\Exceptions\DBQueryException
     */
    public function get(string $fields = '*') : CollectionInterface;

    /**
     * Gets the query result
     * 
     * @param string $fields SQL select statement.
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    public function select(string $fields = '*') : CollectionInterface;

    /**
     * Fetch the result set
     * 
     * @param ?string $class [optional] Model class
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    public function fetch(?string $class = null) : CollectionInterface;

    /**
     * Delete the model
     * 
     * @return bool true if success, false otherwise
     */
    public function delete() : bool;

    /**
     * Destroy all records in the table
     * 
     * @return bool True on success, false on failure
     */
    public static function destroy() : bool;

    /**
     * Make a delete possible but never delete
     * 
     * @return false
     */
    public function softDelete() : bool;

    /**
     * Update model
     * 
     * @param array $value Attributs values key pairs
     * @return bool True on success, false on failure
     * @throws \Clicalmani\Foundation\Exceptions\ModelException
     */
    public function update(array $values = []) : bool;

    /**
     * Insert one or more rows in the table.
     * 
     * @param array $fields Row attributes values
     * @return bool True on success, false on failure
     * @throws \Clicalmani\Foundation\Exceptions\ModelException
     */
    public function insert(array $fields = [], ?bool $replace = false) : bool;

    /**
     * Create a new record and return the instance.
     * If the key is not auto incremented, the key value 
     * will be guessed from the attributes values.
     * 
     * @param array $attributes Attributes values
     * @param bool $replace Replace the record if exists
     * @return self
     */
    public static function create(array $attributes = [], bool $replace = false) : self;

    /**
     * Create a new record or fail
     * 
     * @param ?array $fields
     * @param ?bool $replace
     * @return bool
     */
    public static function createOrFail(array $fields = [], ?bool $replace = false) : bool;

    /**
     * Save changes
     * 
     * @return bool True on success, false on failure
     */
    public function save() : bool;

    /**
     * Save changes quietly
     * 
     * @return bool True on success, false on failure
     */
    public function saveQuietly() : bool;

    /**
     * Returns the last inserted ID for auto incremented keys
     * 
     * @param ?array<string, string> $records A record to guess the ID from (Internal use only)
     * @return mixed
     */
    public function lastInsertId(?array $record = []) : mixed;

    /**
     * Returns the first value in the selected result
     * 
     * @return ?static
     */
    public function first() : ?static;

    /**
     * Returns the first value in the selected result or fail.
     * 
     * @return mixed Returns the model instance if found, otherwise callback result.
     */
    public function firstOr(callable $callback) : mixed;

    /**
     * Returns the first value in the selected result or fail.
     * 
     * @return self
     */
    public function firstOrFail() : self;

    /**
     * Returns a specified row defined by a specified primary key.
     * 
     * @param string|array|null $id Primary key value
     * @return ?self
     */
    public static function find(string|array|null $id) : ?self;

    /**
     * Returns a specified row defined by a specified primary key or fail.
     * 
     * @param string|array|null $id Primary key value
     * @return self
     */
    public static function findOrFail(string|array|null $id) : self;

    /**
     * Returns a specified row defined by a specified primary key or create a new one.
     * 
     * @param string|array|null $id Primary key value
     * @return mixed Returns the model instance if found, otherwise callback result.
     */
    public static function findOr(string|array|null $id, callable $callback) : mixed;

    /**
     * Returns all rows from the query statement result
     * 
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    public static function all() : CollectionInterface;

    /**
     * Filter the query result by using the request parameters. Equal sign 
     * will be used to compare the request parameter value with the column value.
     * 
     * @param array $exclude Parameters to exclude
     * @param array $options Options can be used to order the result set by specifics request parameters or limit the 
     *  number of rows to be returned in the result set.
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    public static function filter(array $exclude = [], array $options = []) : CollectionInterface;

    /**
     * Insert new row or update row from request parameters
     * 
     * @param ?bool $nullify
     * @return void
     */
    public function swap() : void;

    /**
     * Fetch the top $row_count records from the query results set.
     * 
     * @param int $row_count
     * @return self
     */
    public function top(int $row_count) : self;

    /**
     * Re-hydrate the model
     * 
     * @return self
     */
    public function refresh() : self;

    /**
     * Override: Create a seed for the model
     * 
     * @return \Clicalmani\Database\Factory\FactoryInterface
     */
    public static function seed() : \Clicalmani\Database\Factory\FactoryInterface;

    /**
     * Register event
     * 
     * @param string $event Event name
     * @param callable $callback Event handler
     * @return void
     */
    public function registerEvent(string $event, callable $callback): void;

    /**
     * Register observer
     * 
     * @param \Clicalmani\Database\Events\EventObserverInterface $observer
     * @return void
     * @throws \RuntimeException
     */
    public function registerObserver(\Clicalmani\Database\Events\EventObserverInterface $observer): void;

    /**
     * Emit a model event
     * 
     * @param string $event Event name
     * @param mixed $data Event data
     * @return bool
     * @throws \RuntimeException
     */
    public function emit(string $event, mixed $data = null): void;

    /**
     * Switch model connection
     * 
     * @param ?string $connection
     * @return self
     */
    public static function on(?string $connection = null) : self;

    /**
     * Get the model original state
     * 
     * @param ?string $attribute
     * @return string
     */
    public function getOriginal(?string $attribute = null) : mixed;

    /**
     * Mass assignment
     * 
     * @param array $attributes
     * @return self
     */
    public function fill(array $attributes) : self;

    /**
     * Get the first value of a field in the query result
     * 
     * @param string $field Field to first
     * @return mixed
     */
    public function firstValue(string $field) : mixed;

    /**
     * Return the model table name
     * 
     * @param bool $keep_alias Wether to include table alias or not
     * @return string Table name
     */
    public function getTable(bool $keep_alias = false) : string;

    /**
     * Guess key value
     * 
     * @param array $row
     * @return string|array|null
     */
    public function guessKeyValue(array $row) : string|array|null;

    /**
     * Force delete the model when multiple rows must be affected.
     * 
     * @return bool True on success, false on failure
     */
    public function forceDelete() : bool;
}