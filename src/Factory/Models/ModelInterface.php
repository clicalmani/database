<?php
namespace Clicalmani\Database\Factory\Models;

use Clicalmani\Core\Collection\Collection;
use Clicalmani\Core\Collection\CollectionInterface;

interface ModelInterface extends SQLClausesInterface, SQLCasesInterface, Joinable, EventInterface, StateChangeInterface
{
    /**
     * Get the query results.
     * 
     * @param string $fields SQL select statement.
     * @return \Clicalmani\Core\Collection\CollectionInterface
     * @throws \Clicalmani\Database\Exceptions\DBQueryException
     */
    public function get(string $fields = '*') : Collection;

    public function getKey() : Key;

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
     * @return int Affected rows
     * @throws \Clicalmani\Core\Exceptions\ModelException
     */
    public function update(array $values = []) : int;

    /**
     * Insert one or more rows in the table.
     * 
     * @param array $fields Row attributes values
     * @return bool True on success, false on failure
     * @throws \Clicalmani\Core\Exceptions\ModelException
     */
    public function insert(array $fields = []) : bool;

    /**
     * Save changes
     * 
     * @param bool $update Force update if record exists.
     * @return bool True on success, false on failure
     */
    public function save(bool $update = false) : bool;

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
	 * Eager load relationships on an already-fetched collection.
	 *
	 * Unlike with(), which declares relations to load eagerly *before* the
	 * query runs, load() operates on a collection that has already been
	 * fetched. This is useful when you realize after the fact that a
	 * relation is needed, or when the decision to load depends on the
	 * result set itself.
	 *
	 *     $users = User::query()->get();
	 *
	 *     // Later, decide to load orders
	 *     $users->load('orders');
	 *
	 *     // Or load conditionally
	 *     if ($request->get('with_orders')) {
	 *         $users->load('orders.items');
	 *     }
	 *
	 * The method mutates the collection in place and returns it, so it can
	 * be chained:
	 *
	 *     $users = User::query()->get()->load('orders', 'profile');
	 *
	 * Relation names can use dot notation to load nested relations:
	 *
	 *     $users->load('orders.items.product');
	 *
	 * @param string ...$relations One or more relation names to load
	 * @return CollectionInterface
	 */
    public function load(string ...$relations) : CollectionInterface;

    /**
	 * Eager load relationships on an already-fetched collection, using
	 * per-relation closures to customize the relation query.
	 *
	 * Each closure receives the relation's query builder, allowing you to
	 * add constraints, ordering, or field selection:
	 *
	 *     $users->load([
	 *         'orders' => fn($q) => $q->where('status = ?', ['paid']),
	 *         'profile',
	 *         'posts.comments' => fn($q) => $q->orderBy('created_at', 'DESC'),
	 *     ]);
	 *
	 * Dot notation is supported: the closure applies to the deepest segment
	 * of the path.
	 *
	 * @param array $relations Map of relation name => constraint closure
	 * @return CollectionInterface
	 */
    public function loadWithConstraints(array $relations) : CollectionInterface;

    /**
	 * Load missing relationships on an already-fetched collection, skipping
	 * relations that have already been loaded.
	 *
	 * Useful when you're not sure whether a relation was already eager loaded
	 * and want to avoid redundant queries:
	 *
	 *     $users = User::query()->with('profile')->get();
	 *     $users->loadMissing('profile', 'orders'); // 'profile' skipped, 'orders' loaded
	 *
	 * @param string ...$relations One or more relation names to load
	 * @return CollectionInterface
	 */
    public function loadMissing(string ...$relations) : CollectionInterface;
}