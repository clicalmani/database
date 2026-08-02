<?php
namespace Clicalmani\Database\Factory\Models;

use Clicalmani\Database\DBQuery;
use Clicalmani\Database\Factory\Entity;
use Clicalmani\Foundation\Exceptions\ModelException;
use Clicalmani\Foundation\Support\Facades\DB;
use Clicalmani\Foundation\Support\Facades\Str;

/**
 * Class AbstractModel
 * 
 * @package Clicalmani\Foundation
 * @author @clicalmani
 */
abstract class AbstractModel implements Joinable
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
     * Default attributes.
     * 
     * @var array
     */
    protected array $attributes = [];

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
     * With relationships
     * 
     * @var string[]
     */
    protected array $with = [];

    /**
     * Store loaded relations
	 * 
	 * @var array<string, object|null>
     */
    protected array $relations = [];

    /**
	 * Pivot data
	 * 
	 * @var array
	 */
	protected array $pivot = [];

    /**
     * Model entity single instance
     * 
     * @var \Clicalmani\Database\Factory\Entity
     */
    protected $entityInstance;

    /**
     * Auto cast attributes
     * 
     * @var bool
     */
    protected bool $autoCast = true;

    /**
     * Keep attributes fresh
     * 
     * @var array
     */
    protected array $asFresh = [];

    /**
     * Fesh attributes prefix
     * 
     * @var string
     */
    protected string $asFreshPrefix = 'fresh_';

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
    protected abstract function resolveRouteBinding(mixed $value, ?string $field = null) : ?self;

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
        $cleanedKey = $this->cleanKey( $this->primaryKey );
        if (false == $keep_alias) return $cleanedKey;

        return $this->getTableAlias() . '.' . $cleanedKey;
    }

    /**
     * Returns the model table alias
     * 
     * @return string
     */
    public function getTableAlias() : string
    {
        @[$table, $alias] = explode(' ', $this->table);

        return $alias ?? DB::getInstance()->getPrefix() . $table;
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
        
        return $table;
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
    public function isEmpty() : bool
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
     * Alias of getQuery
     * 
     * @return \Clicalmani\Database\DBQuery
     */
    public function newQuery()
    {
        return $this->getQuery();
    }

    /**
     * Get model entity
     * 
     * @return \Clicalmani\Database\Factory\Entity
     */
    public function getEntity()
    {
        if ($this->entityInstance) return $this->entityInstance;
        $entity = new $this->entity;
        $entity->setModel($this);
        $this->entityInstance = $entity;
        return $entity;
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

    public function getAsFreshAttributes() : array
    {
        return $this->asFresh;
    }

    public function getAsFreshPrefix() : string
    {
        return $this->asFreshPrefix;
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

    public function join(string|\Closure|Elegant $model, ?\Closure $callback = null): self
    {
        if (is_string($model)) {
            /** @var \Clicalmani\Database\Factory\Models\Elegant */
            $model = new $model;
        }

        if ($model instanceof \Closure) $this->query->join($model);
        else $this->query->join($model->getTable(true), $callback);
        
        return $this;
    }

    protected function __join(Elegant|string $model, ?string $foreign_key = null, ?string $original_key = null, ?string $type = 'LEFT', ?string $operator = '=') : self 
    {
        if (is_string($model)) {
            $model = new $model;
        }
        
        $table = $model->getTable(true);
        
        /**
         * Duplicate joints
         * 
         * If table is already joint, the first joint will be maintained
         */
        $joints = $this->query->getParam('join');
        
        if ( $joints ) {
            foreach ($joints as $joint) {
                if (@ $joint['table'] == $table) {                            // Table already joint
                    return $this;
                }
            }
        }
        
        [$foreign_key, $original_key] = $this->guessRelationshipKeys($foreign_key, $original_key, (!$foreign_key && !$joints) ? $model::class: $this::class);
        
        $type = ucfirst(strtolower($type));

        if ($type === 'Cross') $this->query->{'join' . $type}($table);
        else $this->query->{'join' . $type}($table, $foreign_key, $original_key, $operator);

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
     * Enable or disable auto-cast
     * 
     * @param ?bool $state
     * @return bool
     */
    public function autoCast(?bool $state = null) : bool
    {
        if (null !== $state) return $this->autoCast = $state;
        return $this->autoCast;
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
        app()->config->set('database.prevent_silent_discard_attribute', true);
    }

    protected function guessRelationshipKeys(?string $foreign_key = null, ?string $original_key = null, ?string $model = null) : array
    {
        // Current table context
        $current_alias = strtolower($this->getTableAlias());
        $current_table_singular = Str::singularize($this->getTable());
        
        // Related table context
        $related_alias = $current_alias;
        $related_table_singular = $current_table_singular;

        if ($model) {
            $model_instance = new $model;
            $related_alias = $model_instance->getTableAlias();
            $related_table_singular = Str::singularize($model_instance->getTable());
        }

        // ---------------------------------------------------------
        // 1 : All keys are provided
        // ---------------------------------------------------------
        if (!is_null($foreign_key) && !is_null($original_key)) {
            return [$foreign_key, $original_key];
        }

        // ---------------------------------------------------------
        // 2 : Only one key is provided
        // ---------------------------------------------------------
        if (!is_null($foreign_key) && is_null($original_key)) {
            return [$foreign_key, $foreign_key];
        }

        // ---------------------------------------------------------
        // 3 : No key is provided, try to guess
        // ---------------------------------------------------------

        if ($model) {
            $fk = ($current_alias ? $current_alias . '.': '') . $related_table_singular . '_id';
            $pk = ($related_alias ? $related_alias . '.': '') . 'id';

            return [$fk, $pk];
        }
        
        // Fallback to default keys
        $fk = $related_table_singular . '_id';
        $pk = $this->getTableAlias() . '.id';
        
        return [$fk, $pk];
    }

    /**
     * @param string $name 
     * @return mixed
     */
    public function __get(string $name) : mixed
    {
        // ── Non Instanciated Model ────────────────────────
        if ( $this->isEmpty() ) return null;
        
        $entity = $this->getEntity();

        $entity->setAccess(Entity::READ_RECORD);
        $attribute = $entity->getAttribute($name); // ── Column Attribute ───────────

        // ── Relation Call ─────────────────────────────────
        // Relationship exists
        if ( array_key_exists($name, $this->relations) ) {
            return $this->relations[$name];
        }

        // When a relation is called as an attribute.
        if ( method_exists($this, $name) ) {
            $relation = $this->{$name}();
            if ( is_subclass_of($relation, \Clicalmani\Database\Factory\Models\Relations\Relationship::class)) {
                return $relation->get();
            }
        }

        // ── Custom Attribute ──────────────────────────────────────────────────
        if ( $attribute->isCustom() ) {
            return $this->{$attribute->customize()}();
        }

        // ── Fresh Attributes ──────────────────────────────────────────────────────
        if (str_starts_with($name, $this->asFreshPrefix)) {
            $name = str_replace($this->asFreshPrefix, '', $name);
            if (in_array($name, $this->asFresh)) {
                return ($this)()->first()?->$name ?? null;
            }
        }
        
        // ── Data Hydaration ───────────────────────────────
        if (!$this->attributeExists($name)) {
            return $this->attributes[$name] ?? null;
        }
        
        try {
            $value = $attribute->value;

            // ── Auto-Cast ──────────────────────────────────────────────────────
            if ($value) {
                /** @var class-string<\Clicalmani\Database\Factory\DataTypes\DataType> */
                $type = $entity->getPropertyType($name);
                (new $type)->cast($value);
                return $value;
            }

            // ── Default Value ──────────────────────────────────────────────────────
            if ( $attribute->isDefault() ) {
                return $attribute->getDefault();
            }
    
            return null;
        } catch (\PDOException $e) {
            return null;
        } catch (\Exception $e) {
            return $attribute->value;
        }
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value) : void
    {
        // ── Model Entity ──────────────────────────────────────────────────────
        $entity = $this->getEntity();
        $found  = false;
        
        /**
         * If column does not exist we keep it as an additional data.
         * So that we can hydrate the model with external data.
         */
        if (!$this->attributeExists($name)) {
            $this->attributes[$name] = $value;
            return;
        }
        
        // Seach for the attribute
        foreach ($entity->getAttributes() as $attribute) {
            if ($attribute->name == $name) {
                $found = true;
                break;
            }
        }
        
        if (false !== $found) {
            if ( $this->id && $this->primaryKey ) {
                $entity->setAccess(Entity::UPDATE_RECORD);  // Update a table row
            } else {
                $entity->setAccess(Entity::ADD_RECORD);     // Create a table row
            }

            $entity->setProperty($name, $value);            // Set the entity property value

        } else {
            $error = sprintf("Error: can not update or insert new record on table %s", $this->table);
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

    /**
     * @return bool
     */
    protected function isSoftDeletable() : bool
    {
        return !!@class_uses($this)[\Clicalmani\Database\Traits\SoftDelete::class];
    }

    /**
     * Verify if an attribute exists
     * 
     * @param string $name Attribute name
     * @return bool
     */
    public function attributeExists(string $name): bool
    {
        return $this->entityInstance->attributeExists($name);
    }
}
