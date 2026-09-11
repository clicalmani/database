<?php
namespace Clicalmani\Database\Factory\Models;

use Clicalmani\Database\DBQuery;
use Clicalmani\Database\Factory\Entity;
use Clicalmani\Database\Factory\Factory;
use Clicalmani\Database\QueryInterface;
use Clicalmani\Foundation\Collection\CollectionInterface;
use Clicalmani\Foundation\Exceptions\ModelException;
use Clicalmani\Foundation\Exceptions\ModelNotFoundException;
use Clicalmani\Foundation\Support\Facades\DB;
use Clicalmani\Foundation\Support\Facades\Str;
use Override;

/**
 * Class Elegant
 * 
 * @package Clicalmani\Foundation
 * @author @clicalmani
 */
class Elegant extends AbstractModel implements ModelInterface, \JsonSerializable
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
    public function isAliasRequired() : bool
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

    protected function exec(string $fields = '*') : CollectionInterface
    {
        try {
            $this->query->set('calc', $this->calc_found_rows); // Set SQL_CALC_FOUND_ROWS flag
            return $this->query->get($fields);                 // Send the request to the request builder
        } catch (\PDOException $e) {
            throw new \Clicalmani\Database\Exceptions\DBQueryException($e->getMessage());
        }
    }

    public function get(?string $fields = '*') : CollectionInterface
    {
        /** @var ?self */
        $model = null;

        if ( $fields && class_exists($fields) ) {
            /** @var ?self */
            $model = new $fields;
            $alias = $model->getTable()->alias();
            $select = "$alias.*";
        } else {                                // Default the current model table's alias
            $model = null;
            $alias = $this->getTable()->alias();
            $select = $fields === '*' ? "$alias.*": $fields;
        }
        
        $results = $this->exec($select)->map(function($row) use($model) {
            $row = (array) $row;

            // If a class is specified create a model of that class
            // By guessing the model key/value paire from the result.
            // Then we instanciate the corresponding model for each result value.
            if ($model) {
                $key = $model->getKey();
                $instance = $model::class::getInstance( array_intersect(array_keys($row), $key->names()) ? $key->fromResult($row)->toValue(): [] );
            }

            // Default to the current model class
            else {
                $key = with( static::getInstance() )->getKey();
                $instance = static::getInstance( array_intersect(array_keys($row), $key->names()) ? $key->fromResult($row)->toValue(): [] );
            }
            
            return $instance->hydrate($row); // Return the model with hydrated data.
        });
        
        if ($this->with) {
            $this->eagerLoad($results);
        }

        return $results;
    }

    public function delete() : bool
    {
        if ( $this->isSoftDeletable() ) return $this->softDelete();

        if ( $this->isEmpty() ) {
            $error = sprintf("Can not update or delete records while on safe mode; on table %s", $this->getTable()->name());
            throw new ModelException($error, ModelException::ERROR_3060);
        }

        if ( empty($this->query->getParam('where')) ) {
            /**
             * Don't add table alias for single delete.
             */
            $this->query->where(...$this->getKey()->toSqlCondition());
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

        $error = sprintf("Can not update or delete records while on safe mode; on table %s", $this->getTable()->name());
        throw new ModelException($error, ModelException::ERROR_3060);
    }

    public function softDelete() : bool
    {
        return  $this->update(['deleted_at' => now()]);
    }

    public function update(array $values = []) : bool
    {
        if (empty($values)) return false;
        
        $criteria = !$this->isEmpty() ? $this->getKey()->toSqlCondition() : [$this->query->getParam('where'), $this->query->getOptions()];
        
        if ( !empty( $criteria[0] ) && !empty( $criteria[1] ) ) {

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
            $this->query->where(...$criteria);
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
            collection( (array) $this->primaryKey )
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
            
            if (FALSE === $this->isEmpty()) {
                $this->reset();
                $this->emit('updated'); 
            }
            
            return $success;
        } 
        
        throw new \Clicalmani\Foundation\Exceptions\ModelException("Can not bulk update or delete records when on safe mode");
    }

    public function insert(array $fields = [], ?bool $update = false) : bool
    {
        if (empty($fields)) return false;
        
        // Before create boot
        $this->emit('creating');

        // Update data
        $data = $this->getData();
        if (array_key_exists('in', $data)) $fields = [$data['in']];
        
        $this->query->unset('tables');
        $this->query->set('type', (FALSE === $update) ? DBQuery::INSERT: DBQuery::REPLACE);
        $this->query->set('table', $this->getTable()->name());
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
                    $error = sprintf("Error: column count doesn't match values count; expected %d, got %d in table %s", count($keys), count(array_keys($field)), $this->getTable()->name());
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
        $this->query->set('tables', [$this->getTable()->withAlias()]);
        
        if (NULL !== $this->id) {
            // After create boot
            $this->emit('created');
        }
        
        return $success;
    }

    public function save(bool $update = false) : bool
    {
        try {
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
                $success = $this->insert( [$data['in']], $update );
            } else {
                $success = true;
            }

            $this->unlock();

            // Reset back to select parameters 
            $this->query->set('type', DBQuery::SELECT);
            $this->query->set('tables', [$this->table]);
            unset($this->query->params['table']);
            
            $this->emit('saved');
            $this->reset();

            return $success;
        } catch (\PDOException $e) {
            throw $e;
        }
    }

    public function saveQuietly() : bool
    {
        return $this->muteEvents()->save();
    }

    public function lastInsertId(array $record = []) : mixed
    {
        $last_insert_id = DB::insertId();

        $key = $this->getKey();
        
        if (!$last_insert_id AND $record) {
            $last_insert_id = $key->fromResult(array_intersect($key->names(), array_keys($record)) ? $record : [])->toValue();
        }

        return $last_insert_id;
    }

    public function first() : ?self
    {
        if ($row = $this->exec()->first()) {
            return static::find($this->getKey()->fromResult((array)$row));
        } 
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

    public function swap() : void
    {
        $columns = \Clicalmani\Database\Factory\Schema::getColumnListing($this->getTable()->name());
        $request = \Clicalmani\Foundation\Http\Request::current();
        
        foreach ($columns as $column) {
            foreach (array_keys($request->all()) as $attribute) {
                if ($column == $attribute) {
                    $this->{$attribute} = $request->{$attribute};
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

    public static function getConnection(): \PDO
    {
        return static::getInstance()->query->getPdo();
    }

    /**
     * Marker for prepared statements. It is used to set the marker for prepared statements in the query.
     *
     * @param ?string $value
     * @return self
     */
    public function marker(?string $value = ':'): self
	{
		$this->query->set('marker', $value);
		return $this;
	}

    public function __toString() : string
    {
        return json_encode( $this );
    }

    public function __invoke(?string $fields = '*')
    {
        return $this->exec($fields);
    }

    public function jsonSerialize() : mixed
    {
        if (!$this->id) return null;

        $entity = $this->getEntity();
        $cache = $entity->getCachedAttributesValues();

        // Attributes
        $data = [];
        $asFresh = [];
        foreach ($entity->getAttributes() as $attribute) {
            if ($attribute->isHidden()) continue;
            $data[$attribute->name] = $cache[$attribute->name] ?? null;
            $this->attributes[] = $attribute->name;

            if ($attribute->keepFresh()) {
                if (empty($asFresh)) {
                    $asFresh = (array)($this)()->first();
                }
                $data[$this->asFreshPrefix . $attribute->name] = $asFresh[$attribute->name] ?? null;
            }
        }
        
        // Custom attributes
        $data2 = [];
        foreach ($entity->getAttributes() as $attribute) {
            if ($attribute->isCustom()) {
                $data2[$attribute->name] = $entity->resolveCustomAttribute($attribute->name);
            }
        }
        
        return array_merge($data, $data2, $this->relations);
    }

    private function eagerLoad(CollectionInterface $results)
    {
        foreach ($this->with as $relation) {
            $parts = explode('.', $relation);
            $root = array_shift($parts);
            if ( !method_exists($this, $root) ) continue;
            
            $reflection = new \ReflectionMethod($this, $root);
            $returnType = $reflection->getReturnType();
            $class = $returnType?->getName();
            
            if ($returnType && is_subclass_of($class, \Clicalmani\Database\Factory\Models\Relations\Relationship::class)) {
                $this->{$root}()->loadNestedRelations($results, $relation);
            }
        }
    }

    public function __serialize()
    {
        return serialize([
            'id' => $this->id
        ]);
    }

    public function __unserialize(array $data)
    {
        // $payload = unserialize($data);
        parent::__construct($data['id']);
    }

    public function getBuilder()
    {
        return $this->query->getBuilder();
    }

    /**
     * Set a relationship on the model
     * 
     * @param string $relation
     * @param mixed $value
     * @return self
     */
    public function setRelation(string|array $relation, mixed $value = null): self
    {
        if ($value === null) {
            $this->with = (array) $relation;
            return $this;
        }

        $parts = explode('.', $relation);
        $root = array_shift($parts);
        
        if (isset($this->relations[$root])) {
            $value = $this->relations[$root];
        } else {
            $this->relations[$root] = $value;
        }

        if ( $parts && method_exists($value, $parts[0]) ) {
            $reflection = new \ReflectionMethod($value, $parts[0]);
            $returnType = $reflection->getReturnType();
            $class = $returnType->getName();

            if ($returnType && is_subclass_of($class, \Clicalmani\Database\Factory\Models\Relations\Relationship::class)) {
                $value->{$parts[0]}()->loadNestedRelations(collect([$value]), join('.', $parts));
            }
        }

        return $this;
    }

    /**
     * Get a relationship
     */
    public function getRelation(string $relation)
    {
        return $this->relations[$relation] ?? null;
    }

    /**
     * Get all relations
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
	 * Extract pivot columns (prefixed with 'pivot_')
	 * 
	 * @param array $data
	 */
	public function hydrate(array $data): self
    {
        if ($data) {
            $pivotData = [];
            foreach ($data as $key => $value) {
                if (strpos($key, 'pivot_') === 0) {
                    $pivotKey = substr($key, 6);
                    $pivotData[$pivotKey] = $value;
                    unset($data[$key]);
                }
            }
            
            $this->pivot = $pivotData;

            $entity = $this->getEntity();
            $attributeValues = $entity->getCachedAttributesValues();

            // Set other attributes
            foreach ($data as $key => $value) {
                if ($this->attributeExists($key)) {
                    $attributeValues[$key] = $value;
                } else $this->{$key} = $value;
            }

            $entity->setCachedAttributesValues($attributeValues);
        }
        
        return $this;
    }

    public function setPivot(array $pivot): self
    {
        $this->pivot = $pivot;
        return $this;
    }

    public function getPivot(): array
    {
        return $this->pivot;
    }

    /**
     * Pivot
     * 
     * @param class-string<self> $pivotClass
     * @param ?string $foreignKey
     * @return self
     */
    public function pivotLeft(string $pivotClass, ?string $foreignKey = null, ?string $parentKey = null)
    {
        return $this->pivot($pivotClass, $foreignKey);
    }

    /**
     * Pivot
     * 
     * @param class-string<self> $pivotClass
     * @param ?string $foreignKey
     * @return self
     */
    public function pivotRight(string $pivotClass, ?string $foreignKey = null)
    {
        return $this->pivot($pivotClass, $foreignKey, 'right');
    }

    /**
     * Pivot
     * @return self
     */
    private function pivot(string $pivotClass, ?string $foreignKey = null, ?string $direction = 'left'): self
    {
        $indexes = ['left' => 0, 'right' => 1]; // Direction indexes

        $pivotModel = new $pivotClass;
        $foreignKey = $foreignKey ?? Str::singularize($this->getTable()->name()) . '_id';

        $tables = explode('_', $pivotModel->getTable()->name());

        if ( count($table) !== 2 ) {
            return $this;
        }

        $secondKey  = $tables[$indexes[$direction]] . '_id'; 

        $rows = $pivotModel->newQuery()
                    ->where("{$foreignKey} = ?", [$this->{$this->getKey()->scalarName()}])
                    ->get();

        $data = [];
        foreach ($rows as $row) {
            $data[$row->{$foreignKey}] = $row->{$secondKey};
        }

        return $this->setPivot($data);
    }
}
