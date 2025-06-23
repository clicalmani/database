<?php
namespace Clicalmani\Database\Factory\Models;

use Clicalmani\Database\DBQuery;
use Clicalmani\Database\Factory\Entity;
use Clicalmani\Foundation\Exceptions\ModelException;
use Clicalmani\Foundation\Support\Facades\DB;

/**
 * Class AbstractModel
 * 
 * @package Clicalmani\Foundation
 * @author @clicalmani
 */
abstract class AbstractModel implements Joinable, \JsonSerializable
{
    use MultipleKeys;

    /**
     * Database connection
     * 
     * @var string Connection name
     */
    protected $connection;

    /**
     * Primary key value
     * 
     * @var string|array
     */
    protected $id;

    /**
     * DBQuery object
     * 
     * @var \Clicalmani\Database\DBQuery
     */
    protected $query;

    /**
     * Model table
     * 
     * @var string Table name
     */
    protected $table;

    /**
     * Model table singular
     * 
     * @var string Table name
     */
    protected $table_singular;

    /**
     * Default attributes.
     * 
     * @var array
     */
    protected $attributes = [];

    /**
     * Model entity
     * 
     * @var \Clicalmani\Database\Factory\Entity
     */
    protected string $entity;
    
    /**
     * Table primary key
     * 
     * @var string|array Primary key
     */
    protected $primaryKey;

    /**
     * Hidden attributes.
     * 
     * @var string[]
     */
    protected $hidden = [];

    /**
     * Fillable attributes
     * 
     * @var string[]
     */
    protected $fillable = [];

    /**
     * Guarded attributes
     * 
     * @var string[]
     */
    protected $guarded = [];

    /**
     * Lock state
     * 
     * @var bool
     */
    protected $locked = false;

    /**
     * Append custom attributes
     * 
     * @var string[] Custom attributes
     */
    protected $custom = [];

    /**
     * Date attributes
     * 
     * @var string[]
     */
    protected $dates = [];

    /**
     * Handle SQL IGNORE
     * 
     * @var bool Default to false
     */
    protected $insert_ignore = false;

    /**
     * Handle SQL DISTINCT 
     * 
     * @var bool Default to false
     */
    protected $distinct = false;

    /**
     * Enable pagination
     * 
     * @var bool Default to false
     */
    protected $calc_found_rows = false;

    /**
     * Event handlers
     * 
     * @var array<string, callable>
     */
    protected $eventHandlers = [];

    /**
     * Dispatch custom events
     * 
     * @var array<string, callable|string>
     */
    protected $dispatchesEvents = [];

    /**
     * Events observers
     * 
     * @var string[]
     */
    protected $observers = [];

    /**
     * Model entity single instance
     * 
     * @var \Clicalmani\Database\Factory\Entity
     */
    private $entity_instance;

    /**
     * Register model events
     * 
     * @return void
     */
    protected abstract function booted() : void;

    /**
     * Resolve route binding.
     * 
     * @return static|null
     */
    protected abstract function resolveRouteBinding(mixed $value, ?string $field = null) : static|null;

    /**
     * Emit event
     * 
     * @param string $event Event name
     * @param mixed $data Event data
     * @return void
     */
    abstract protected function emit(string $event, mixed $data = null) : void;

    /**
     * Constructor
     * 
     * @param array|string|null $id
     */
    public function __construct(array|string|null $id = null)
    {
        $this->id    = $id;
        $this->query = new DBQuery;

        $this->query->set('tables', [$this->table]);

        if ( isset($this->connection) ) {
            $this->query->set('connection', $this->connection);
        }

        /**
         * Register model events.
         */
        $this->booted();

        /**
         * Register observers
         */
        foreach ($this->observers as $observer) {
            $observer = new $observer;
            if ( method_exists($observer, 'observe') ) {
                $observer->observe($this);
            } else {
                throw new ModelException(
                    sprintf("Observer %s must inherit from % class.", $observer::class, \Clicalmani\Database\Events\EventObserver::class),
                );
            }
        }
    }

    /**
     * Returns table primary key name.
     * 
     * @param bool $keep_alias When true table alias will be prepended to the key.
     * @return string|array
     */
    public function getKey(bool $keep_alias = false) : string|array
    {
        if (false == $keep_alias) return $this->cleanKey( $this->primaryKey );

        return $this->primaryKey;
    }

    /**
     * Return the model table name
     * 
     * @param bool $keep_alias Wether to include table alias or not
     * @return string Table name
     */
    public function getTable(bool $keep_alias = false) : string
    {
        if ($keep_alias) return $this->table;
       
        @[$table, $alias] = explode(' ', $this->table);

        return $alias ? $table: $this->table;
    }

    /**
     * Enable lock state
     * 
     * @return void
     */
    protected function lock(?string $type = 'WRITE', ?bool $disable_keys = false) : void
    {
        $this->locked = DB::table($this->table)->lock($type, $disable_keys);
    }

    /**
     * Disable lock state
     * 
     * @return void
     */
    protected function unlock(?bool $enable_keys = false) : void
    {
        $this->locked = !DB::table($this->table)->unlock($enable_keys);
    }

    /**
     * Verify lock state
     * 
     * @return bool
     */
    protected function isLocked() : bool
    {
        return $this->locked;
    }

    /**
     * Verify if model is defined
     * 
     * @return bool
     */
    protected function isEmpty() : bool
    {
        return !($this->id && $this->primaryKey);
    }

    /**
     * Get model manupulated data
     * 
     * @return array
     */
    protected function getData() : array
    {
        $in = [];
        $out = [];

        $entity = $this->getEntity();
        $entity->setModel($this);
        
        foreach ($entity->getAttributes() as $attribute) {
            
            // Escape none fillable attributes for update
            if ( FALSE === $attribute->isFillable() && $attribute->access === Attribute::UPDATE) continue;
            
            // Nullify entry value if not defined
            $value = !$attribute->isNull() ? $attribute->value: null;
            
            if ($attribute->access === Attribute::INSERT && $entity->isWriting($attribute->name)) $in[$attribute->name] = $value;
            elseif ($attribute->access === Attribute::UPDATE && $entity->isUpdating($attribute->name)) $out[$attribute->name] = $value;
        }

        // Append default values
        foreach ($this->attributes as $name => $default_value) {
            if ( !isset($in[$name]) && !isset($out[$name]) ) {
                if ( $entity->getAccess() === Entity::ADD_RECORD ) $in[$name] = $default_value;
                elseif ( $entity->getAccess() === Entity::UPDATE_RECORD ) $out[$name] = $default_value;
            }
        }

        if ( $in ) return ['in' => $in];
        if ( $out ) return ['out' => $out];

        return [];
    }

    /**
     * Query getter
     * 
     * @return \Clicalmani\Database\DBQuery
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get model entity
     * 
     * @return \Clicalmani\Database\Factory\Entity
     */
    public function getEntity()
    {
        if ($this->entity_instance) return $this->entity_instance;
        return tap(new $this->entity, fn(Entity $entity) => $this->entity_instance = $entity);
    }

    /**
     * Fillable getter
     * 
     * @return string[]
     */
    public function getFillableAttributes() : array
    {
        return $this->fillable;
    }

    /**
     * Hidden getter
     * 
     * @return string[]
     */
    public function getHiddenAttributes() : array
    {
        return $this->hidden;
    }

    /**
     * Custom getter
     * 
     * @return string[]
     */
    public function getCustomAttributes() : array
    {
        return $this->custom;
    }

    /**
     * Default attributes getter
     * 
     * @return array
     */
    public function getDefaultAttributes() : array
    {
        return $this->attributes;
    }

    /**
     * Get attribute default value
     * 
     * @param string $name
     * @return string
     */
    public function getDefault(string $name) : mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function join(string|callable|Elegant $model, ?callable $callback = null): self
    {
        if (is_string($model)) {
            /** @var \Clicalmani\Database\Factory\Models\Elegant */
            $model = new $model;
            $this->query->join($model->getTable(true), $callback);
        } elseif (is_callable($model)) $this->query->join($model);
        
        return $this;
    }

    protected function __join(Elegant|string $model, ?string $foreign_key = null, ?string $original_key = null, ?string $type = 'LEFT', ?string $operator = '=') : self 
    {
        [$foreign_key, $original_key] = $this->guessRelationshipKeys($foreign_key, $original_key);

        if (is_string($model)) {
            $model = new $model;
        }

        /**
         * Duplicate joints
         * 
         * If table is already joint, the first joint will be maintained
         */
        $joints = $this->query->getParam('join');

        if ( $joints ) {
            foreach ($joints as $joint) {
                if (@ $joint['table'] == $model->getTable(true)) {                            // Table already joint
                    return $this;
                }
            }
        }

        $type = ucfirst(strtolower($type));

        if ($type === 'Cross') $this->query->{'join' . $type}($model->getTable(true));
        else $this->query->{'join' . $type}($model->getTable(true), $foreign_key, $original_key, $operator);

        return $this;
    }

    public function leftJoin(Elegant|string $model, ?string $foreign_key = null, ?string $original_key = null, ?string $operator = '='): static
    {
        return $this->__join($model, $foreign_key, $original_key, 'LEFT', $operator);
    }

    public function rightJoin(Elegant|string $model, ?string $foreign_key = null, ?string $original_key = null, ?string $operator = '='): static
    {
        return $this->__join($model, $foreign_key, $original_key, 'RIGHT', $operator);
    }

    public function innerJoin(Elegant|string $model, ?string $foreign_key = null, ?string $original_key = null, ?string $operator = '='): static
    {
        return $this->__join($model, $foreign_key, $original_key, 'INNER', $operator);
    }

    public function crossJoin(Elegant|string $model): static
    {
        return $this->__join($model, null, null, 'CROSS');
    }

    public function jsonSerialize() : mixed
    {
        if (!$this->id) return null;

        $row = DB::table($this->getTable())->where($this->getKeySQLCondition())->get()->first();
        
        if ( !$row ) return null;

        $entity = $this->getEntity();
        $entity->setModel($this);

        $seemsJson = function(string $name, mixed &$value) use($entity) {
            try {
                $type = $entity->getPropertyType($name);

                if ($type === \Clicalmani\Database\DataTypes\Json::class) {
                    $value = (new $type)->decode($value);
                }
            } catch (\Exception $e) {}
        };

        // Attributes
        $data = [];
        foreach ($row as $name => $value) {
            $entity->setAccess(Entity::READ_RECORD);
            $attribute = $entity->getAttribute($name);
            $seemsJson($name, $value);
            $attribute->value = $value;

            if ($attribute->isHidden()) continue;

            $data[$attribute->name] = $attribute->isNull() ? null: $attribute->value;
            $this->attributes[] = $attribute->name;
        }
        
        // Custom attributes
        $data2 = [];

        foreach ($this->custom as $name) {
            $entity->setAccess(Entity::READ_RECORD);
            $attribute = $entity->getAttribute($name);
            $seemsJson($name, $value);
            $attribute->value = $value;

            $data2[$name] = $attribute->getCustomValue();
        }
        
        return array_merge($data, $data2);
    }

    /**
     * Set model connection
     * 
     * @param string $connection
     * @return static
     */
    public function setConnection(string $connection) : static
    {
        $this->connection = $connection;
        $this->query->set('connection', $connection);
        return $this;
    }

    /**
     * Resolve route binding using a callback.
     * 
     * @param \Closure $callback
     * @return void
     */
    public static function resolveRouteBindingUsing(\Closure $callback) : void
    {
        \App\Providers\RouteServiceProvider::routeBindingCallback($callback);
    }

    /**
     * Protect attributes from mass assignment
     * 
     * @param array &$attributes
     * @throws \Clicalmani\Database\Exceptions\MassAssignmentException
     * @return void
     */
    protected function discardGuardedAttributes(array &$attributes) : void
    {
        $keys = array_keys($attributes);
        $attributes_discarded = false;
        
        if ( $this->guarded ) {
            $arr = array_diff($keys, $this->guarded);
            $attributes_discarded = true;
        } elseif ( $this->fillable ) {
            $arr = array_intersect($keys, $this->fillable);
            $attributes_discarded = true;
        }

        if ( !empty($arr) ) {
            foreach ($arr as $key) {
                if ( !array_key_exists($key, $attributes) ) unset($attributes[$key]);
            }
        }
        
        if (TRUE === $attributes_discarded && app()->config->database('prevent_silent_discard_attribute')) {
            $class = self::class;
            throw new \Clicalmani\Database\Exceptions\MassAssignmentException(
                sprintf("Trying to mass assign $class while in mass assignment preventing mode")
            );
        }
    }

    /**
     * Prevent silent discard attribute setting
     * 
     * @return void
     */
    public static function preventSilentlyDiscardingAttributes() : void
    {
        $db_config = app()->config->database();
        $db_config['prevent_silent_discard_attribute'] = true;
        app()->database = $db_config;
    }

    protected function guessRelationshipKeys(?string $foreign_key = null, ?string $original_key = null, ?string $model = null) : array
    {
        $table = strtolower($model ? (new $model)->getTable(true): $this->getTable(true));
        $arr = explode(' ', $table);
        $table = $arr[0];
        $alias = count($arr) === 2 ? end($arr) : '';
        
        // Singular form guessing
        $table = $this->table_singular ?: $table;
        
        if ( ! isset($foreign_key) ) {
            return ["{$table}_id", $alias ? "{$alias}.id": 'id'];
        }

        $original_key = $original_key ?? $foreign_key;                              // The original key is the parent
                                                                                    // primary key
        
        if ($original_key == $foreign_key) {
            $original_key = $this->cleanKey($original_key);
            $foreign_key  = $original_key;
        }

        return [$foreign_key, $original_key];
    }

    /**
     * @param string $name 
     * @return mixed
     */
    public function __get(string $name) : mixed
    {
        if ( empty($name) || $this->isEmpty() ) return null;
        
        $entity = $this->getEntity();
        $entity->setModel($this);
        $entity->setAccess(Entity::READ_RECORD);
        $attribute = $entity->getAttribute($name);
        
        try {
            if ( $attribute->isCustom() ) {
                return $this->{$attribute->customize()}();
            }
    
            /**
             * Hold up joints because the request will be made on the main query
             */
            $joint = $this->query->getParam('join');
            $this->query->unset('join');
            
            $collection = $this->query->set('where', $this->getKeySQLCondition(true))->get("`$name`");
            
            /**
             * Restore joints
             */
            $this->query->set('join', $joint);
            
            if ($row = $collection->first()) {

                $value = $row[$name];
                $type = $entity->getPropertyType($name);

                if ($type === \Clicalmani\Database\DataTypes\Json::class) {
                    $value = (new $type)->decode($value);
                }
                
                return $value;
            }

            if ( $attribute->isDefault() ) {
                return $attribute->getDefault();
            }
    
            return null;
        } catch (\PDOException $e) {
            console_log($e->getMessage(), __FILE__, __LINE__);
            return null;
        }
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value) : void
    {
        $db = DB::getInstance();
        $table = $db->getPrefix() . $this->getTable();
        $statement = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . env('DB_NAME', '') . "' AND TABLE_NAME = '$table'");
        $found = false;
        
        while($row = $db->fetch($statement, \PDO::FETCH_NUM)) {
            if ($row[0] == $name) {
                $found = true;
                break;
            }
        }

        if (false !== $found) {

            $entity = $this->getEntity();
            $entity->setModel($this);

            if ( $this->id && $this->primaryKey ) {

                $entity->setAccess(Entity::UPDATE_RECORD);

            } else {

                $entity->setAccess(Entity::ADD_RECORD);

            }

            $entity->setProperty($name, $value);

        } else {
            $error = sprintf("Error: can not update or insert new record on table %s", $this->getTable());
            throw new ModelException($error, ModelException::ERROR_3060);
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset(string $name) : bool
    {
        return isset($this->{$name});
    }

    /**
     * @param string $name
     * @return void
     */
    public function __unset(string $name) : void
    {
        unset($this->{$name});
    }
}
