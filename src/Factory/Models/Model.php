<?php
namespace Clicalmani\Database\Factory\Models;

use Clicalmani\Foundation\Collection\Collection;
use Clicalmani\Database\DB;
use Clicalmani\Database\DBQuery;
use Clicalmani\Database\Factory\Factory;
use Clicalmani\Database\Factory\Models\Events\Event;
use Clicalmani\Foundation\Exceptions\ModelException;

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
    use SQLTriggers;

    /**
     * Enable model events trigger
     * 
     * @var bool Enabled by default
     */
    public static $triggerEvents = true;

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
    
            $this->query->set('distinct', $this->select_distinct); // Set SQL DISTINCT flag
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
        $this->emit('before_delete');

        // Restore params
        $this->query->params = $params;
        
        $success = $this->query->delete()->exec()->status() == 'success';

        // After delete boot
        $this->emit('after_delete');

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
                $this->emit('before_update');

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
            
            if (FALSE === $this->isEmpty()) $this->emit('after_update'); 
            
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
        $this->emit('before_create');

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
            $this->emit('after_create');
        }
        
        return $success;
    }

    /**
     * Alias of insert
     * 
     * @param array $fields Attributes values
     * @return bool True if success, false otherwise
     */
    public function create(array $fields = [], ?bool $replace = false) : bool
    {
        return $this->insert($fields, $replace);
    }

    /**
     * Create a new record or fail
     * 
     * @param ?array $fields
     * @param ?bool $replace
     * @return bool
     */
    public function createOrFail(array $fields = [], ?bool $replace = false) : bool
    {
        return DB::getInstance()->beginTransaction(fn() => $this->create($fields, $replace));
    }

    /**
     * Save changes
     * 
     * @return bool True on success, false on failure
     */
    public function save() : bool
    {
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

        return $success;
    }

    /**
     * Returns the last inserted ID for auto incremented keys
     * 
     * @param ?array<string, string> $records A record to guess the ID from (Internal use only)
     * @return mixed
     */
    public function lastInsertId(?array $record = []) : mixed
    {
        $last_insert_id = DB::getPdo()->lastInsertId();
        
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
     * @return static|null
     */
    public function firstOr(callable $callback) : static|null
    {
        if (NULL !== $row = $this->first()) return $row;

        $callback();
        return null;
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
     * Filter the current SQL statement result by using a provided request query parameters.
     * The query parameters will be automatically fetched. A filter can be used to exclude some
     * specific parameters. 
     * 
     * @param array $exclude Parameters to exclude
     * @param array $options Options can be used to order the result set by specifics request parameters or limit the 
     *  number of rows to be returned in the result set.
     * @return \Clicalmani\Foundation\Collection\Collection
     */
    public static function filter(array $exclude = [], array $options = []) : Collection
    {
        $options = (object) $options;

        /**
         * |---------------------------------------------------
         * |              ***** Notice *****
         * |---------------------------------------------------
         * test_user_id and hash are two request parameters internally used by flesco.
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
    private function registerEvent(string $event, callable $callback): void
    {
        $handler = null;
        
        if (static::$triggerEvents AND $callback AND is_callable($callback, true, $handler)) {
            $this->eventHandlers[$event] = $callback;
        }
    }

    /**
     * @return bool
     */
    private function isSoftDeletable() : bool
    {
        return $this->dates && in_array('deleted_at', $this->dates);
    }

    public function emit(string $event, mixed $data = null): void
    {
        if ( $handler = @ $this->eventHandlers[$event] ) {

            /**
             * Lock
             */
            if ( strpos($event, 'before') ) $this->lock();
            
            $handler($this);

            /**
             * Release
             */
            if ( $this->isLocked() ) $this->unlock();
        }

        /**
         * Custom events
         */
        else {
            $customEvents = \App\Providers\EventServiceProvider::getEvents();
            
            $this->lock();

            foreach ($customEvents as $name => $listeners) {
                
                if ($name !== $event) continue;

                $event = new Event;
                $event->name = $name;
                $event->target = $this;
                
                foreach ($listeners as $listener) {
                    if ( is_callable($listener) ) $listener($event, $data);
                    else instance($listener, fn($inst) => $inst->handler($event, $data));
                }
            }

            $this->unlock();
        }
    }

    public function __toString() : string
    {
        return json_encode( $this );
    }
}
