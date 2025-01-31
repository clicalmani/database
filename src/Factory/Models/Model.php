<?php
namespace Clicalmani\Database\Factory\Models;

use Clicalmani\Foundation\Collection\Collection;
use Clicalmani\Database\DB;
use Clicalmani\Database\DBQuery;
use Clicalmani\Database\Factory\Factory;
use Clicalmani\Foundation\Exceptions\ModelException;
use Clicalmani\Foundation\Exceptions\ModelNotFoundException;

/**
 * Class Model
 * 
 * @package Clicalmani\Foundation
 * @author @clicalmani
 */
class Model extends AbstractModel implements DataClauseInterface, DataOptionInterface
{
    use SQLClauses;
    use SQLCases;
    use RelationShips;
    use CaptureEvents;
    use SQLAggregate;
    use StateChange;

    public function __construct(array|string|null $id = null)
    {
        parent::__construct($id);
    }
    
    /**
     * Verify if table has alias
     * 
     * @return bool True if defined, false otherwise
     */
    private function isAliasRequired() : bool
    {
        /**
         * Escape insert query
         */
        if ( $this->query->getParam('table') ) return false;

        /**
         * If table has alias then it is required for attributes
         */
        if ( $this->query->getParam('tables') ) 
            foreach ($this->query->getParam('tables') as $table ) {
                if ( count( explode(' ', $table) ) > 1) return true;
            }
        
        return false;
    }

    /**
     * Internal wrap of the PHP builtin function get_called_class()
     * 
     * @see get_called_class() function
     * @return string Class name
     */
    protected static function getClassName() : string
    {
        return get_called_class();
    }

    /**
     * Return the model instance. Usefull for static methods call.
     * 
     * @param string|array $id [optional] Primary key value
     * @return static
     */
    private static function getInstance($id = null) : static
    {
        $class = static::getClassName();
        return with ( new $class($id) );
    }

    /**
     * Get the query results.
     * 
     * @param ?string $fields SQL select statement.
     * @return \Clicalmani\Foundation\Collection\Collection
     */
    public function get(?string $fields = '*') : Collection
    {
        try {
            if ( !$this->query->getParam('where') AND $this->id) {
                $this->query->set('where', $this->getKeySQLCondition( $this->isAliasRequired() ));
            }

            // Exclude soft deleted records from the query results.
            if ( $this->isSoftDeletable() ) {
                $this->getQuery()->where('deleted_at IS NULL');
            }
    
            $this->query->set('distinct', $this->distinct); // Set SQL DISTINCT flag
            $this->query->set('calc', $this->calc_found_rows);     // Set SQL_CALC_FOUND_ROWS flag
            
            return $this->query->get($fields);
            
        } catch (\PDOException $e) {
            throw new \Clicalmani\Database\Exceptions\DBQueryException($e->getMessage());
        }
    }

    /**
     * Gets the query result
     * 
     * @param ?string $fields SQL select statement.
     * @return \Clicalmani\Foundation\Collection\Collection
     */
    public function select(?string $fields = '*') : Collection
    {
        return $this->get($fields);
    }

    /**
     * Fetch the result set
     * 
     * @param ?string $class [optional] Model class
     * @return \Clicalmani\Foundation\Collection\Collection
     */
    public function fetch(?string $class = null) : Collection
    {
        return $this->get()->map(function($row) use($class) {
            if ($class) return $class::getInstance( with( new $class )->guessKeyValue($row) );
            return static::getInstance( with( static::getInstance() )->guessKeyValue($row) );
        });
    }

    /**
     * Delete the model
     * 
     * @return bool true if success, false otherwise
     */
    public function delete() : bool
    {
        if ( $this->isSoftDeletable() ) return $this->softDelete();

        if ( $this->isEmpty() ) {
            $error = sprintf("Can not update or delete records while on safe mode; on table %s", $this->getTable());
            throw new ModelException($error, ModelException::ERROR_3060);
        }

        if ( empty($this->query->getParam('where')) ) {
            /**
             * Don't add table alias for single delete.
             */
            $this->query->set('where', $this->getKeySQLCondition( count( $this->query->getParam('tables') ) > 1 ? true: false ));
        }

        // Save params
        $params = $this->query->params;
        
        // Before delete boot
        $this->emit('deleting');

        // Restore params
        $this->query->params = $params;
        
        $success = $this->query->delete()->exec()->status() == 'success';

        // After delete boot
        $this->emit('deleted');

        return $success;
    }

    /**
     * Force delete the model when multiple rows must be affected.
     * 
     * @return bool True on success, false on failure
     */
    public function forceDelete() : bool
    {
        /**
         * A delete operation must be set on a condition.
         * We first check the query where parameter.
         */
        if (!empty($this->query->params['where'])) return $this->query->delete()->exec()->status() === 'success';

        $error = sprintf("Can not update or delete records while on safe mode; on table %s", $this->getTable());
        throw new ModelException($error, ModelException::ERROR_3060);
    }

    /**
     * Destroy all records in the table
     * 
     * @return bool True on success, false on failure
     */
    public static function destroy() : bool
    {
        $instance = static::getInstance();
        $instance->query->set('table', $instance->getTable());
        return $instance->query->truncate();
    }

    /**
     * Make a delete possible but never delete
     * 
     * @return false
     */
    public function softDelete() : bool
    {
        return  $this->update(['deleted_at' => now()]);
    }

    /**
     * Update model
     * 
     * @param ?array $value Attributs values key pairs
     * @return bool True on success, false on failure
     */
    public function update(?array $values = []) : bool
    {
        if (empty($values)) return false;
        
        if (FALSE === $this->isEmpty()) {
            $criteria = $this->getKeySQLCondition();
        } else {
            $criteria = $this->query->getParam('where');
        }
        
        if ( !empty( $criteria ) ) {

            if (FALSE === $this->isEmpty()) {
                $this->emit('updating');

                /** @var array */
                $data = $this->getData();
                if (array_key_exists('out', $data)) $values = $data['out'];
            }
            
            $fields = array_keys( $values );
		    $values = array_values( $values );

            $this->query->set('type', DBQuery::UPDATE);
            $this->query->set('fields',  $fields);
		    $this->query->set('values', $values);
            $this->query->set('where', $criteria);
            $this->query->set('ignore', $this->insert_ignore); // Set SQL IGNORE flag
            
            $success = $this->query->exec()->status() === 'success';

            $record = [];       // Updated attributes

            foreach ($fields as $index => $attr) {
                $record[$attr] = $values[$index];
            }
            
            /**
             * Check key change: When key change we must update the current stored key.
             * 
             * Verify whether key(s) is/are among the updated attributes
             */
            collection( (array) $this->cleanKey($this->primaryKey) )
                ->map(function($pkey, $index) use($record) {
                    if ( array_key_exists($pkey, $record) ) {               // The current key has been updated
                        if ( is_string($this->id) ) {
                            $this->id = $record[$pkey];                     // Update key value
                            return;
                        }

                        $this->id[$index] = $record[$pkey];                 // Update key value
                    }
                });

            // Restore state
            $this->query->set('type', DBQuery::SELECT);
            
            if (FALSE === $this->isEmpty()) $this->emit('updated'); 
            
            return $success;
        } 
        
        throw new \Exception("Can not bulk update or delete records when on safe mode");
    }

    /**
     * Insert one or more rows in the table.
     * 
     * @param array $fields Row attributes values
     * @return bool True on success, false on failure
     */
    public function insert(array $fields = [], ?bool $replace = false) : bool
    {
        if (empty($fields)) return false;
        
        // Before create boot
        $this->emit('creating');

        // Update data
        $data = $this->getData();
        if (array_key_exists('in', $data)) $fields = [$data['in']];
        
        $this->query->unset('tables');
        $this->query->set('type', (FALSE === $replace) ? DBQuery::INSERT: DBQuery::REPLACE);
        $this->query->set('table', $this->getTable());
        $this->query->set('ignore', $this->insert_ignore); // Set SQL IGNORE flag

        $keys = [];
        $values = [];

        foreach ($fields as $field) {
            $this->discardGuardedAttributes($field);
            if (empty($keys)) $keys = array_keys($field);
            
            /**
             * Each entry must be checked to make sure column count match values count.
             */
            else {
                if (count($keys) !== count(array_keys($field))) {
                    $error = sprintf("Error: column count doesn't match values count; expected %d, got %d in table %s", count($keys), count(array_keys($field)), $this->getTable());
                    throw new ModelException($error, ModelException::ERROR_3050);
                }
            }

            $values[] = array_values($field);
        }
        
        $this->query->set('fields', $keys);
        $this->query->set('values', $values);

        $success = $this->query->exec()->status() === 'success';

        $values = end($values);

        $record = [];

        foreach ($keys as $index => $key) {
            $record[$key] = $values[$index];
        }
        
        $this->id = $this->lastInsertId($record);

        $this->query->unset('table');
        $this->query->set('type', DBQuery::SELECT);
        $this->query->set('tables', [$this->getTable(true)]);
        
        if (NULL !== $this->id) {
            // After create boot
            $this->emit('created');
        }
        
        return $success;
    }

    /**
     * Create a new record and return the instance.
     * If the key is not auto incremented, the key value 
     * will be guessed from the attributes values.
     * 
     * @param array $attributes Attributes values
     * @param ?bool $replace Replace the record if exists
     * @return static
     */
    public static function create(array $attributes = [], ?bool $replace = false) : static
    {
        $instance = static::getInstance();
        $instance->insert($attributes, $replace);
        
        if ($last_insert_id = $instance->lastInsertId()) {
            return static::find($last_insert_id);
        }

        return static::find( $instance->guessKeyValue($attributes) );
    }

    /**
     * Create a new record or fail
     * 
     * @param ?array $fields
     * @param ?bool $replace
     * @return bool
     */
    public static function createOrFail(array $fields = [], ?bool $replace = false) : bool
    {
        return DB::transaction(fn() => static::getInstance()->insert($fields, $replace));
    }

    /**
     * Save changes
     * 
     * @return bool True on success, false on failure
     */
    public function save() : bool
    {
        $this->emit('saving');

        $success = false;
        $data = $this->getData();
        
        $this->lock();
        
        if ( @ $data['out'] ) {
            /**
             * Update
             */
            $success = $this->update( $data['out'] );
        } elseif ( @ $data['in'] ) {
            /**
             * Insert
             */
            $success = $this->insert( [$data['in']] );
        }

        $this->unlock();

        // Reset back to select parameters 
        // $this->data = [];
        $this->query->set('type', DBQuery::SELECT);
        $this->query->set('tables', [$this->table]);
        unset($this->query->params['table']);

        $this->emit('saved');

        return $success;
    }

    /**
     * Save changes quietly
     * 
     * @return bool True on success, false on failure
     */
    public function saveQuietly() : bool
    {
        return $this->muteEvents()->save();
    }

    /**
     * Returns the last inserted ID for auto incremented keys
     * 
     * @param ?array<string, string> $records A record to guess the ID from (Internal use only)
     * @return mixed
     */
    public function lastInsertId(?array $record = []) : mixed
    {
        $last_insert_id = DB::insertId();
        
        if (!$last_insert_id AND $record) {
            $last_insert_id = $this->guessKeyValue($record);
        }

        return $last_insert_id;
    }

    /**
     * Returns the first value in the selected result
     * 
     * @return static|null
     */
    public function first() : static|null
    {
        if ($row = $this->get()->first()) 
            return static::find( $this->guessKeyValue($row) );

        return null;
    }

    /**
     * Returns the first value in the selected result or fail.
     * 
     * @return mixed Returns the model instance if found, otherwise callback result.
     */
    public function firstOr(callable $callback) : mixed
    {
        if (NULL !== $row = $this->first()) return $row;

        return $callback();
    }

    /**
     * Returns the first value in the selected result or fail.
     * 
     * @return static
     */
    public function firstOrFail() : static
    {
        try {
            return $this->first() ?? throw new ModelNotFoundException("Model not found", 404);
        } catch (ModelNotFoundException $e) {
            throw $e;
        }
    }
    
    /**
     * Returns a specified row defined by a specified primary key.
     * 
     * @param string|array|null $id Primary key value
     * @return static|null
     */
    public static function find(string|array|null $id) : static|null
    {
        if (!$id) return null;
        return static::getInstance($id);
    }

    /**
     * Returns a specified row defined by a specified primary key or fail.
     * 
     * @param string|array|null $id Primary key value
     * @return static
     */
    public static function findOrFail(string|array|null $id) : static
    {
        try {
            return static::find($id) ?? throw new ModelNotFoundException("Model not found", 404);
        } catch (ModelNotFoundException $e) {
            throw $e;
        }
    }

    /**
     * Returns a specified row defined by a specified primary key or create a new one.
     * 
     * @param string|array|null $id Primary key value
     * @return mixed Returns the model instance if found, otherwise callback result.
     */
    public static function findOr(string|array|null $id, callable $callback) : mixed
    {
        $instance = static::getInstance($id);

        if (NULL !== $row = $instance->first()) return $row;

        return $callback();
    }

    /**
     * Returns all rows from the query statement result
     * 
     * @return \Clicalmani\Foundation\Collection\Collection
     */
    public static function all() : Collection
    {
        $instance = static::getInstance();
        
        return $instance->get()->map(function($row) use($instance) {
            return static::getInstance( $instance->guessKeyValue($row) );
        });
    }

    /**
     * Filter the query result by using the request parameters. Equal sign 
     * will be used to compare the request parameter value with the column value.
     * 
     * @param array $exclude Parameters to exclude
     * @param array $options Options can be used to order the result set by specifics request parameters or limit the 
     *  number of rows to be returned in the result set.
     * @return \Clicalmani\Foundation\Collection\Collection
     */
    public static function filter(?array $exclude = [], ?array $options = []) : Collection
    {
        $options = (object) $options;

        /**
         * |---------------------------------------------------
         * |              ***** Notice *****
         * |---------------------------------------------------
         * test_user_id and hash are two request parameters internally used by Tonka.
         * test_user_id holds the request user ID in test mode.
         * and hash is used for url encryption.
         */
        $filters     = with (new \Clicalmani\Foundation\Http\Requests\Request)->where(array_merge($exclude, ['test_user_id', 'hash']));
        $child_class = static::getClassName();

        $criteria = '1';
        
        if ( $filters ) {
            $criteria = join(' AND ', $filters);
        }

        try {
            $obj = $child_class::where($criteria);

            if (@ $options?->order_by) {
                $obj->orderBy($options->order_by);
            }

            if (NULL !== @ $options?->offset && @ $options?->limit) {
                $obj->limit(@ $options?->offset, $options->limit);
            }
            
            return $obj->fetch();

        } catch (\PDOException $e) {
            return collection();
        }
    }

    /**
     * Insert new row or update row from request parameters
     * 
     * @param ?bool $nullify
     * @return static
     */
    public function swap() : static
    {
        $db        = DB::getInstance();
        $table     = $db->getPrefix() . $this->getTable();
        $statement = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . env('DB_NAME', '') . "' AND TABLE_NAME = '$table'");
        
        while($row = $db->fetch($statement, \PDO::FETCH_NUM)) {
            foreach (array_keys(request()) as $attribute) {
                if ($row[0] == $attribute) {
                    $this->{$attribute} = request($attribute);
                    break;
                }
            }
        }
        
        return $this;
    }

    /**
     * Fetch the top $row_count records from the query results set.
     * 
     * @param int $row_count
     * @return static
     */
    public function top(int $row_count) : static
    {
        return $this->limit(0, $row_count);
    }

    /**
     * Re-hydrate the model
     * 
     * @return static
     */
    public function refresh() : static
    {
        return static::find($this->id);
    }

    /**
     * Override: Create a seed for the model
     * 
     * @return \Clicalmani\Database\Factory\Factory
     */
    public static function seed()
    {
        return Factory::new();
    }

    protected function resolveRouteBinding(mixed $value, ?string $field = null) : static|null
    {
        return null;
    }

    /**
     * Register event
     * 
     * @param string $event Event name
     * @param callable $callback Event handler
     * @return void
     */
    public function registerEvent(string $event, callable $callback): void
    {
        if (FALSE == self::isEventsCapturingPrevented() && FALSE === $this->isCustomEvent($event) && is_callable($callback)) {
            $this->eventHandlers[$event] = $callback;
        }
    }

    /**
     * Register observer
     * 
     * @param \Clicalmani\Database\Events\EventObserverInterface $observer
     * @return void
     * @throws \RuntimeException
     */
    public function registerObserver(\Clicalmani\Database\Events\EventObserverInterface $observer): void
    {
        $reflection = new \ReflectionClass($observer);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if ( $this->isEvent($method->name) ) {

                if ( array_key_exists($method->name, $this->eventHandlers) ) {
                    throw new \RuntimeException(
                        sprintf("Failed to register observer %s, event %s is already registered.", get_class($observer), $method->name)
                    );
                }

                $this->observers[$method->name] = [$observer, $method->name];
            }
        }
    }

    /**
     * @return bool
     */
    private function isSoftDeletable() : bool
    {
        return $this->dates && in_array('deleted_at', $this->dates);
    }

    /**
     * Emit a model event
     * 
     * @param string $event Event name
     * @param mixed $data Event data
     * @return bool
     * @throws \RuntimeException
     */
    public function emit(string $event, mixed $data = null): void
    {
        if ( FALSE === $this->isEvent($event) ) 
            throw new \RuntimeException(
                sprintf("Failed to emit %s, make sure it is a registered event.", $event)
            );

        while(DB::inTransaction()) self::preventEventsCapturing();

        self::allowEventsCapturing();
        $this->triggerEvent($event, $data);
    }

    /**
     * Switch model connection
     * 
     * @param ?string $connection
     * @return static
     */
    public static function on(string $connection = null) : static
    {
        $instance = static::getInstance();
        $instance->connection = $connection;
        return $instance;
    }

    /**
     * Get the model original state
     * 
     * @param ?string $attribute
     * @return string
     */
    public function getOriginal(?string $attribute = null) : mixed
    {
        $manipulated = $this->getData();

        if ( !isset($attribute) ) return @$manipulated['in'] ?? @$manipulated['out'] ?? [];

        if ( array_key_exists($attribute, @$manipulated['in'] ?? []) ) return $manipulated['in'][$attribute];

        if ( array_key_exists($attribute, @$manipulated['out'] ?? []) ) return $manipulated['out'][$attribute];

        return null;
    }

    /**
     * Mass assignment
     * 
     * @param array $attributes
     * @return static
     */
    public function fill(array $attributes) : static
    {
        $this->discardGuardedAttributes($attributes);

        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    /**
     * Get the first value of a field in the query result
     * 
     * @param string $field Field to first
     * @return mixed
     */
    public function firstValue(string $field) : mixed
    {
        return $this->query->firstValue($field);
    }

    public function __toString() : string
    {
        return json_encode( $this );
    }
}
