<?php
namespace Clicalmani\Database\Factory\Models;

use Clicalmani\Database\DBQuery;
use Clicalmani\Database\Factory\Factory;
use Clicalmani\Foundation\Collection\CollectionInterface;
use Clicalmani\Foundation\Exceptions\ModelException;
use Clicalmani\Foundation\Exceptions\ModelNotFoundException;
use Clicalmani\Foundation\Support\Facades\DB;

/**
 * Class Elegant
 * 
 * @package Clicalmani\Foundation
 * @author @clicalmani
 */
class Elegant extends AbstractModel implements ModelInterface
{
    use SQLClauses;
    use SQLCases;
    use Relationships;
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
     * @return self
     */
    private static function getInstance($id = null) : self
    {
        $class = static::getClassName();
        return with ( new $class($id) );
    }

    public function get(string $fields = '*') : CollectionInterface
    {
        try {
            if ( !$this->query->getParam('where') AND $this->id) {
                $this->query->set('where', $this->getKeySQLCondition( $this->isAliasRequired() ));
            }

            // Exclude soft deleted records from the query results.
            if ( $this->isSoftDeletable() ) {
                $this->query->set('recycle', $this->query->getParam('recycle') ?? 1);
            }
    
            $this->query->set('distinct', $this->distinct); // Set SQL DISTINCT flag
            $this->query->set('calc', $this->calc_found_rows);     // Set SQL_CALC_FOUND_ROWS flag
            
            return $this->query->get($fields);
            
        } catch (\PDOException $e) {
            throw new \Clicalmani\Database\Exceptions\DBQueryException($e->getMessage());
        }
    }

    public function select(string $fields = '*') : CollectionInterface
    {
        return $this->get($fields);
    }

    public function fetch(?string $class = null) : CollectionInterface
    {
        return $this->get()->map(function($row) use($class) {
            if ($class) return $class::getInstance( with( new $class )->guessKeyValue($row) );
            return static::getInstance( with( static::getInstance() )->guessKeyValue($row) );
        });
    }

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

    public static function destroy() : bool
    {
        $instance = static::getInstance();
        $instance->query->set('table', $instance->getTable());
        return $instance->query->truncate();
    }

    public function softDelete() : bool
    {
        return  $this->update(['deleted_at' => now()]);
    }

    public function update(array $values = []) : bool
    {
        if (empty($values)) return false;
        
        $criteria = !$this->isEmpty() ? $this->getKeySQLCondition() : $criteria = $this->query->getParam('where');
        
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
        
        throw new \Clicalmani\Foundation\Exceptions\ModelException("Can not bulk update or delete records when on safe mode");
    }

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

    public static function create(array $attributes = [], bool $replace = false) : self
    {
        $attributes = [$attributes];
        /** @var self */
        $instance = static::getInstance();
        
        if ($id = $instance->getQuery()->insertGetId($attributes) ?: $instance->guessKeyValue($attributes)) {
            return self::find($id);
        }
        
        return new self;
    }

    public static function createOrFail(array $fields = [], ?bool $replace = false) : bool
    {
        return DB::transaction(fn() => static::getInstance()->insert($fields, $replace));
    }

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

    public function saveQuietly() : bool
    {
        return $this->muteEvents()->save();
    }

    public function lastInsertId(?array $record = []) : mixed
    {
        $last_insert_id = DB::insertId();
        
        if (!$last_insert_id AND $record) {
            $last_insert_id = $this->guessKeyValue($record);
        }

        return $last_insert_id;
    }

    public function first() : ?static
    {
        if ($row = $this->get()->first()) 
            return static::find( $this->guessKeyValue($row) );

        return null;
    }

    public function firstOr(callable $callback) : mixed
    {
        if (NULL !== $row = $this->first()) return $row;

        return $callback();
    }

    public function firstOrFail() : self
    {
        try {
            return $this->first() ?? throw new ModelNotFoundException("Model not found", 404);
        } catch (ModelNotFoundException $e) {
            throw $e;
        }
    }
    
    public static function find(string|array|null $id) : ?self
    {
        return static::getInstance($id);
    }

    public static function findOrFail(string|array|null $id) : self
    {
        $instance = self::find($id);
        return @$instance->get()->{$instance->getKey()} ? $instance: throw new ModelNotFoundException("Model not found", 404);
    }

    public static function findOr(string|array|null $id, callable $callback) : mixed
    {
        $instance = static::getInstance($id);

        if (NULL !== $row = $instance->first()) return $row;

        return $callback();
    }

    public static function all() : CollectionInterface
    {
        $instance = static::getInstance();
        
        return $instance->get()->map(function($row) use($instance) {
            return static::getInstance( $instance->guessKeyValue($row) );
        });
    }

    public static function filter(array $exclude = [], array $options = []) : CollectionInterface
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
        $filters     = with (new \Clicalmani\Foundation\Http\Request)->where(array_merge($exclude, ['test_user_id', 'hash']));
        $child_class = static::getClassName();

        $criteria = true;
        
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

    public function swap() : void
    {
        $table     = DB::getPrefix() . $this->getTable();
        $statement = DB::query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . env('DB_NAME', '') . "' AND TABLE_NAME = '$table'");
        
        while($row = DB::fetch($statement, \PDO::FETCH_NUM)) {
            foreach (array_keys(request()) as $attribute) {
                if ($row[0] == $attribute) {
                    $this->{$attribute} = request($attribute);
                    break;
                }
            }
        }
    }

    public function top(int $row_count) : self
    {
        return $this->limit(0, $row_count);
    }

    public function refresh() : self
    {
        return static::find($this->id);
    }

    public static function seed() : \Clicalmani\Database\Factory\FactoryInterface
    {
        return Factory::new();
    }

    protected function resolveRouteBinding(mixed $value, ?string $field = null) : ?self
    {
        return null;
    }

    public function registerEvent(string $event, callable $callback): void
    {
        if (FALSE == self::isEventsCapturingPrevented() && FALSE === $this->isCustomEvent($event) && is_callable($callback)) {
            $this->eventHandlers[$event] = $callback;
        }
    }

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

    public function emit(string $event, mixed $data = null): void
    {
        if ( FALSE === $this->isEvent($event) ) 
            throw new \RuntimeException(
                sprintf("Failed to emit %s, make sure it is a registered event.", $event)
            );

        if (DB::inTransaction()) self::preventEventsCapturing();

        $this->triggerEvent($event, $data);
        self::allowEventsCapturing();
    }

    public static function on(?string $connection = null) : self
    {
        $instance = static::getInstance();
        $instance->connection = $connection;
        return $instance;
    }

    public function getOriginal(?string $attribute = null) : mixed
    {
        if ( !isset($attribute) ) return $this;
        return $this->{$attribute};
    }
    
    public function getDirty(?string $attribute = null) : mixed
    {
        $manipulated = $this->getData();

        if ( !isset($attribute) ) return @$manipulated['in'] ?? @$manipulated['out'] ?? [];

        if ( array_key_exists($attribute, @$manipulated['in'] ?? []) ) return $manipulated['in'][$attribute];

        if ( array_key_exists($attribute, @$manipulated['out'] ?? []) ) return $manipulated['out'][$attribute];

        return null;
    }

    public function fill(array $attributes) : self
    {
        $this->discardGuardedAttributes($attributes);

        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    public function firstValue(string $field) : mixed
    {
        return $this->query->firstValue($field);
    }

    public function union(self $model, bool $all = false) : self
    {
        $this->query->union($model->getQuery(), $all);
        return $this;
    }

    public function __toString() : string
    {
        return json_encode( $this );
    }
}
