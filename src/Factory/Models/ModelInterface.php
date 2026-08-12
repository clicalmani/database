<?php
namespace Clicalmani\Database\Factory\Models;

use Clicalmani\Foundation\Collection\CollectionInterface;

interface ModelInterface extends SQLClausesInterface, SQLCasesInterface, Joinable, SQLAggregateInterface, EventInterface, StateChangeInterface
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
     * Delete the model
     * 
     * @return bool true if success, false otherwise
     */
    public function delete() : bool;

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
    public function lastInsertId(array $record = []) : mixed;

    /**
     * Returns the first value in the selected result
     * 
     * @return ?self
     */
    public function first() : ?self;

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
     * @return static|null
     */
    public static function find(string|array|null $id) : static|null;

    public static function findMany(array $ids) : CollectionInterface;

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
     * Get the top $row_count records from the query results set.
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
     * Get the attributes that have been manipulated (set) since model instantiation.
     * 
     * @param string|null $attribute [optional] Attribute name
     * @return mixed Manipulated attributes or specific attribute value
     */
    public function getDirty(?string $attribute = null) : mixed;

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
     * @return Table
     */
    public function getTable() : Table;

    /**
     * Force delete the model when multiple rows must be affected.
     * 
     * @return bool True on success, false on failure
     */
    public function forceDelete() : bool;

    /**
	 * Combine the result sets of two queries
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Elegant $model
	 * @param bool $all
	 * @return self
	 */
    public function union(\Clicalmani\Database\Factory\Models\Elegant $model, bool $all = false) : self;

    /**
     * Get the model connection
     * 
     * @return \PDO
     */
    public static function getConnection(): \PDO;

    public function scopeWith(string|array $relations): self;

    /**
     * Marker for prepared statements. It is used to set the marker for prepared statements in the query.
     *
     * @param ?string $value
     * @return self
     */
    public function marker(?string $value = ':'): self;
}