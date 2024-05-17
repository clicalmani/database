<?php
namespace Clicalmani\Database\Factory\Models;

use Clicalmani\Collection\Collection;
use Clicalmani\Database\DB;
use Clicalmani\Database\DBQuery;
use Clicalmani\Database\Factory\Factory;
use Clicalmani\Database\Factory\Models\Events\Event;
use Clicalmani\Flesco\Exceptions\ModelException;

/**
 * Class Model
 * 
 * @package Clicalmani\Flesco
 * @author @clicalmani
 */
class Model extends AbstractModel implements DataClauseInterface, DataOptionInterface
{
    /**
     * Enable model events trigger
     * 
     * @var bool Enabled by default
     */
    public static $triggerEvents = true;

    public function __construct(array|string|null $id = null)
    {
        parent::__construct($id);
        
        /**
         * Trigger model events
         */
        $this->boot();
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
     * @return static|null
     */
    private static function getInstance(string|array $id = null) : static|null
    {
        $class = static::getClassName();
        return with ( new $class($id) );
    }

    /**
     * Gets the query result
     * 
     * @param ?string $fields SQL select statement.
     * @return \Clicalmani\Collection\Collection
     */
    public function get(?string $fields = '*') : Collection
    {
        try {
            if ( !$this->query->getParam('where') AND $this->id) {
                $this->query->set('where', $this->getKeySQLCondition( $this->isAliasRequired() ));
            }
    
            $this->query->set('distinct', $this->select_distinct); // Set SQL DISTINCT flag
            $this->query->set('calc', $this->calc_found_rows);     // Set SQL_CALC_FOUND_ROWS flag
            
            return $this->query->get($fields);
            
        } catch (\PDOException $e) {
            throw new \Clicalmani\Database\Exceptions\DBQueryException($e->getMessage());
        }
    }

    /**
     * Instead of returning a raw SQL statement result, every row in the result set will be
     * matched to a table model.
     * 
     * @param ?string $class Model class to be returned. If not specified the outer left table model of the joint will be used.
     * @return \Clicalmani\Collection\Collection
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

        // Before delete boot
        $this->triggerEvent('before_delete');
        
        $success = $this->query->delete()->exec()->status() == 'success';

        // After delete boot
        $this->triggerEvent('after_delete');

        return $success;
    }

    /**
     * Force delete the model when multiple rows must be affected.
     * 
     * @return bool True on success, false on failure
     */
    public function forceDelete() : bool
    {
        if (FALSE === $this->isEmpty()) return $this->delete();

        /**
         * A delete operation must be set on a condition.
         * We first check the query where parameter.
         */
        if (!empty($this->query->params['where'])) return $this->query->delete()->exec()->status() == 'success';

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
        return DB::getInstance()->beginTransaction(function() {
            $this->delete();
            return false;
        });
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
                $this->triggerEvent('before_update');

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
            collection( (array) $this->clean($this->primaryKey) )
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
            
            if (FALSE === $this->isEmpty()) $this->triggerEvent('after_update'); 
            
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
    public function insert(array $fields = []) : bool
    {
        if (empty($fields)) return false;
        
        // Before create boot
        $this->triggerEvent('before_create');

        // Update data
        $data = $this->getData();
        if (array_key_exists('in', $data)) $fields = [$data['in']];
        
        $this->query->unset('tables');
        $this->query->set('type', DBQuery::INSERT);
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
            $this->triggerEvent('after_create');
        }
        
        return $success;
    }

    /**
     * Alias of insert
     * 
     * @param array $fields Attributes values
     * @return bool True if success, false otherwise
     */
    public function create(array $fields = []) : bool
    {
        return $this->insert($fields);
    }

    /**
     * Same as create with the difference of catching PDOException in case 
     * the query statement execution failed. Useful when catching the status result.
     * 
     * @param array $fields Attributes values
     * @return bool True if success, false otherwise
     */
    public function createOrFail(array $fields = []) : bool
    {
        try {
            return $this->create($fields);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * In pratice a model can be joined to another model. But in particular situation, one may be aiming to join
     * the current model to a sub query (as this is possible with SQL). So this method allows such an operation.
     * 
     * @param string $query_callback
     * @param ?array $options
     * @return static
     */
    public function subQuery(string|callable $query_callback, ?array $options = []) : static
    {
        if ( is_string($query_callback) ) $this->query->set('sub_query', $query_callback);
        else {

            if ( array_diff(['join', 'alias', 'condition', 'subfields'], array_keys($options)) ) throw new \Exception(
                sprintf("Invalid option provided for %s::%s function", $this::class, 'subQuery()')
            );

            /** @var \Clicalmani\Database\Factory\Models\Model */
            $model = $query_callback();
            $model->get($options['subfields'] ?? '*');
            $builder = $model->getQuery()->getBuilder();
            $sql = $builder->getSQL();

            $joints = $this->query->getParam('join') ?? [];

            $joints[] = [
                'sub_query' => $sql,
                'type' => $options['join'],
                'alias' => $options['alias'],
                'criteria' => $options['condition']
            ];

            $this->query->set('join', $joints);
            // $this->query->set('sub_query', "{$options['join']} ($sql) {$options['alias']} {$options['condition']}");

            unset($query);
            unset($builder);
            unset($sql);
            unset($joint);
        }

        return $this;
    }

    /**
     * The current model inherit a foreign key
     * We should match the model key value to obtain its parent.
     * 
     * @param string $class Parent model
     * @param string $foreign_key [Optional] Table foreign key
     * @param string $parent_key [Optional] original key
     * @return mixed
     */
    protected function belongsTo(string $class, string|null $foreign_key = null, string|null $original_key = null) : mixed
    {
        return ( new $class )->join($this, $foreign_key, $original_key)
                    ->whereAnd($this->getKeySQLCondition(true))
                    ->fetch()
                    ->first();
    }

    /**
     * One an one relationship: the current model inherit a foreign key
     * 
     * @param string $class Parent model
     * @param string $foreign_key [Optional] Table foreign key
     * @param string $parent_key [Optional] original key
     * @return mixed
     */
    protected function hasOne(string $class, string|null $foreign_key = null, string|null $original_key = null) : mixed
    {
        if ( $this->isEmpty() ) return null;
        
        return $this->join($class, $foreign_key, $original_key)
                    ->fetch($class)
                    ->first();
    }

    /**
     * One to many relationship
     * 
     * @param string $class Child model
     * @param string $foreign_key [Optional] Table foreign key
     * @param string $original_key [Optional] Original key
     * @return \Clicalmani\Collection\Collection
     */
    protected function hasMany(string $class, ?string $foreign_key = null, ?string $original_key = null) : Collection
    {
        if ( $this->isEmpty() ) return collection();
        
        return $this->getInstance($this->id)
                    ->join($class, $foreign_key, $original_key)
                    ->fetch($class)
                    ->filter(fn($obj) => !$obj->isEmpty());  // Avoid empty records
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
     * @return \Clicalmani\Collection\Collection
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
     * @return \Clicalmani\Collection\Collection
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
        $filters     = with (new \Clicalmani\Flesco\Http\Requests\Request)->where(array_merge($exclude, ['test_user_id', 'hash']));
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

            if (@ $options?->offset) {
                $obj->offset($options->offset);
            }

            if (@ $options?->limit) {
                $obj->limit($options->limit);
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

    public static function where(?string $criteria = '1', ?array $options = []) : static
    {
        $instance = static::getInstance();
        $instance->getQuery()->where($criteria, 'AND', $options);

        return $instance;
    }

    public function whereAnd(?string $criteria = '1', ?array $options = []) : static
    {
        $this->query->where($criteria, 'AND', $options);
        return $this;
    }

    public function whereOr(string $criteria = '1', ?array $options = []) : static
    {
        $this->query->where($criteria, 'OR', $options);
        return $this;
    }
    
    public function orderBy(string $order) : static
    {
        $this->query->params['order_by'] = $order;
        return $this;
    }

    public function having(string $criteria) : static
    {
        $this->query->having($criteria);
        return $this;
    }

    public function groupBy(string $criteria, ?bool $with_rollup = false) : static
    {
        if ($with_rollup) $criteria .= ' WITH ROLLUP';
        $this->query->groupBy($criteria);
        return $this;
    }

    public function from(string $fields) : static
    {
        $this->query->from($fields);
        return $this;
    }

    public function limit(?int $offset = 0, ?int $row_count = 1) : static
    {
        $this->query->set('offset', $offset);
        $this->query->set('limit', $row_count);
        return $this;
    }

    public function ignore(bool $ignore = true) : static
    {
        $this->insert_ignore = $ignore;
        return $this;
    }

    public function distinct(bool $distinct = true) : static
    {
        $this->select_distinct = $distinct;
        return $this;
    }
    
    public function calcFoundRows(bool $calc = true) : static
    {
        $this->calc_found_rows = $calc;
        return $this;
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

    /**
     * Before create trigger
     * 
     * @param callable $callback Event handler
     * @return void
     */
    protected function beforeCreate(callable $callback) : void
    {
        $this->registerEvent('before_create', $callback);
    }

    /**
     * After create trigger
     * 
     * @param callable $callback Event handler
     * @return static
     */
    protected function afterCreate(callable $callback) : void
    {
        $this->registerEvent('after_create', $callback);
    }

    /**
     * Before update trigger
     * 
     * @param callable $callback Event handler
     * @return void
     */
    protected function beforeUpdate(callable $callback) : void
    {
        $this->registerEvent('before_update', $callback);
    }

    /**
     * After update trigger
     * 
     * @param callable $callback Event handler
     * @return void
     */
    protected function afterUpdate(callable $callback) : void
    {
        $this->registerEvent('after_update', $callback);
    }

    /**
     * Before delete trigger
     * 
     * @param callable $callback Event handler
     * @return void
     */
    protected function beforeDelete(callable $callback) : void
    {
        $this->registerEvent('before_delete', $callback);
    }

    /**
     * After delete trigger
     * 
     * @param callable $callback Event handler
     * @return void
     */
    protected function afterDelete(callable $callback) : void
    {
        $this->registerEvent('after_delete', $callback);
    }

    protected function boot() : void
    {
        /**
         * TODO
         */
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
        if (static::$triggerEvents AND $callback AND is_callable($callback, true, $handler)) {
            $this->eventHandlers[$event] = $callback;
        }
    }

    public function triggerEvent(string $event, mixed $data = null): void
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
                    else instance($listener, fn($inst) => $inst->listener($event, $data));
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
