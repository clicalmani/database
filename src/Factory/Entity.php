<?php
namespace Clicalmani\Database\Factory;

use Clicalmani\Database\DBQuery;
use Clicalmani\Database\Factory\AlterOption;
use Clicalmani\Database\Factory\DataTypes\DataType;
use Clicalmani\Database\Factory\DefaultCollation;
use Clicalmani\Database\Factory\Index as IndexType;
use Clicalmani\Database\Factory\Indexes\Index;
use Clicalmani\Database\Factory\Maker;
use Clicalmani\Database\Factory\PrimaryKey;
use Clicalmani\Database\Factory\Property;
use Clicalmani\Database\Factory\Models\Attribute;
use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Database\Factory\Models\Key;
use Clicalmani\Foundation\Support\Facades\Log;
use Clicalmani\Validation\Validator;

/**
 * Class Entity
 * 
 * Serves as an abstract base class for database schema mapping, record lifecycle tracking, 
 * data type validation, and automatic schema migrations using PHP Reflection and Attributes.
 * 
 * @package Clicalmani\Database\Factory
 * @author @clicalmani
 */
abstract class Entity 
{
    /**
     * Read-only operations mode.
     * 
     * @var int
     */
    const READ_RECORD = 0;

    /**
     * Update operations mode.
     * 
     * @var int
     */
    const UPDATE_RECORD = 1;

    /**
     * Insert/Creation operations mode.
     * 
     * @var int
     */
    const ADD_RECORD = 2;

    /**
     * The underlying data model instance.
     * 
     * @var \Clicalmani\Database\Factory\Models\Elegant
     */
    protected \Clicalmani\Database\Factory\Models\Elegant $model;

    /**
     * Tracks attributes currently undergoing hook resolution to prevent infinite loops.
     * 
     * @var array<string, bool>
     */
    private array $resolvingHooks = [];

    /**
     * The current access mode of the entity (Read, Update, or Add).
     * 
     * @var int
     */
    protected $access;

    /**
     * List of attribute names flagged for insertion.
     * 
     * @var string[]
     */
    protected array $new_records = [];

    /**
     * List of attribute names flagged for update.
     * 
     * @var string[]
     */
    protected array $updated_records = [];

    /**
     * Indicates whether the entity's database attributes have been loaded.
     * 
     * @var bool
     */
    protected bool $attributesLoaded = false;

    /**
     * Stored database state values for hydrated attributes.
     * 
     * @var array<string, mixed>
     */
    protected array $attributeValues = [];

    /**
     * Custom user-defined or runtime attributes.
     * 
     * @var array
     */
    protected array $customAttributes = [];

    /**
     * Reflects upon public properties to dynamically retrieve all entity attributes.
     * 
     * @return \Clicalmani\Database\Factory\Models\Attribute[]
     */
    public function getAttributes() : array
    {
        $ret = [];

        $reflection = new \ReflectionClass($this);
        $public_properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        foreach ($public_properties as $property) {
            $name = $property->getName();
            $isCustom = method_exists($property, 'hasHook') && $property->hasHook(\PropertyHookType::Get);
            
            // NEVER trigger getValue() on a property that utilizes a getter hook here.
            // Doing so would execute the underlying hook logic (e.g., resolving a dynamic relation)
            // prematurely, simply for the sake of mapping out the attribute matrix.
            $value = (!$isCustom && $property->isInitialized($this))
                        ? $property->getValue($this)
                        : null;

            $attribute = new Attribute($name, $value);
            $attribute->model    = $this->model;
            $attribute->access   = $this->access;
            $attribute->isCustom = $isCustom;
            
            $ret[] = $attribute;
        }

        return $ret;
    }

    /**
     * Retrieves an attribute wrapper instance by its field name.
     * 
     * @param string $name Attribute name.
     * @return \Clicalmani\Database\Factory\Models\Attribute
     */
    public function getAttribute(string $name) : Attribute
    {
        $this->loadAttributes();
        return tap(new Attribute($name), function(Attribute $attribute) use($name) {
            $attribute->model = $this->model;
            $attribute->value = $attribute->isCustom() ? $attribute->getCustomValue(): $this->attributeValues[$name] ?? null;
            $attribute->model = $this->model;
            $attribute->access = $this->access;
        });
    }

    /**
     * Returns the cached map of loaded attribute values.
     * 
     * @return array<string, mixed>
     */
    public function getCachedAttributesValues(): array
    {
        $this->loadAttributes();
        return $this->attributeValues;
    }

    /**
     * Overrides the local state cache with new values and re-triggers lazy hydration.
     * 
     * @param array<string, mixed> $newValues
     * @return void
     */
    public function setCachedAttributesValues(array $newValues): void
    {
        $this->attributeValues  = $newValues;
        $this->attributesLoaded = false;
        $this->loadAttributes();
    }

    /**
     * Invokes a custom attribute's get hook safely, guarding against
     * re-entrant resolution (e.g. a getter that logs/serializes the model,
     * which in turn tries to re-resolve every custom attribute).
     * 
     * @param string $name Attribute name.
     * @throws \LogicException If a circular dependency or infinite re-entrance loop occurs.
     * @return mixed
     */
    public function resolveCustomAttribute(string $name): mixed
    {
        if (isset($this->resolvingHooks[$name])) {
            throw new \LogicException(
                sprintf("Circular resolution detected for custom attribute [%s] on entity %s.", $name, static::class)
            );
        }

        $this->resolvingHooks[$name] = true;

        try {
            $reflector = new \ReflectionProperty($this, $name);
            $getHook = $reflector->getHook(\PropertyHookType::Get);
            return $getHook->invoke($this);
        } finally {
            unset($this->resolvingHooks[$name]);
        }
    }

    /**
     * Retrieves the current structural operations mode.
     * 
     * @return int
     */
    public function getAccess() : int
    {
        return $this->access;
    }

    /**
     * Updates the local operations access context mode.
     * 
     * @param int $access
     * @return void
     */
    public function setAccess(int $access) : void
    {
        $this->access = $access;
    }

    /**
     * Retrieves the underlying database persistence model.
     * 
     * @return \Clicalmani\Database\Factory\Models\Elegant
     */
    public function getModel() : Elegant
    {
        return $this->model;
    }

    /**
     * Assigns the entity's relational database bridge model.
     * 
     * @param \Clicalmani\Database\Factory\Models\Elegant $model
     * @return void
     */
    public function setModel(Elegant $model) : void
    {
        $this->model = $model;
    }

    /**
     * Sets an internal database property value, parsing validations, custom 
     * structural attributes, data-type mapping, and caching lifecycle state.
     * 
     * @param string $name Property name.
     * @param mixed $value Property value.
     * @return void
     */
    public function setProperty(string $name, mixed $value) : void
    {
        $attr = new Attribute($name, $value);
        $attr->model = $this->model;

        if ($attr->isCustom() || !$this->isWritable()) {
            return;
        }

        try {
            $value = $this->validateProperty($name, $value);
            $this->assignTypedProperty($name, $value);
        } catch (\ReflectionException $e) {
            Log::error($e->getMessage(), E_ERROR, __CLASS__, __LINE__);
        } catch (\Exception $e) {
            Log::error($e->getMessage(), E_ERROR, __CLASS__, __LINE__);
        }

        $this->trackRecordChange($name);
    }

    /**
     * Resolves the fully qualified class name or base type configuration of a property.
     * Supports complex Reflection Union types.
     * 
     * @param string $name Property name.
     * @throws \Exception If the property's structural type constraint cannot be resolved.
     * @return string
     */
    public function getPropertyType(string $name)
    {
        $property = ( new \ReflectionProperty($this, $name) );

        if (property_exists($this, $name) && $property->hasType()) {
            
            $type = $property->getType();

            if ($type instanceof \ReflectionUnionType) {
                $types = $property->getType()->getTypes();
                foreach ($types as $tp) {
                    if ($tp instanceof \ReflectionNamedType) {
                        $name = $tp->getName();
                        if (is_subclass_of($name, DataType::class)) return $name;
                    }
                }
            }

            return $type->getName();
        }
        
        throw new \Exception();
    }

    /**
     * Verifies if a given attribute is currently marked for a fresh database insertion.
     * 
     * @param string $name Attribute name.
     * @return bool TRUE if registering an insertion, FALSE otherwise.
     */
    public function isWriting(string $name) : bool
    {
        return in_array($name, $this->new_records);
    }

    /**
     * Verifies if a given attribute is currently tracked for updates.
     * 
     * @param string $name Attribute name.
     * @return bool TRUE if flagged for alteration, FALSE otherwise.
     */
    public function isUpdating(string $name) : bool
    {
        return in_array($name, $this->updated_records);
    }

    /**
     * Checks if a mapped attribute exists on the current entity object context.
     * 
     * @param string $name
     * @return bool
     */
    public function attributeExists(string $name): bool
    {
        return !!collect($this->getAttributes())->find(fn(Attribute $attribute) => $attribute->name === $name);
    }

    /**
     * Compiles dynamic runtime fields to execute schema creation or migrations.
     * 
     * @param ?bool $exec Run statement if true, otherwise compile and output.
     * @param ?string $dump_file Target migration backup manifest file.
     * @return bool TRUE on success, FALSE otherwise.
     */
    public function migrate(?bool $exec = true, ?string $dump_file = null) : bool
    {
        $table = $this->model->getTable()->name();

        $query = new DBQuery;
        $query->set('type', DBQuery::CREATE);
        $query->set('table', $table);

        $definition = $this->buildColumnDefinitions();

        if ($pk = $this->buildPrimaryKeyDefinition()) {
            $definition[] = $pk;
        }

        $definition = array_merge($definition, $this->buildIndexDefinitions());

        $this->applyCollation($query);
        $this->applyEngine($query);

        $alter = $this->applyAlterOption($query, $definition);

        $query->set('definition', $definition);

        $success = $this->build($query->exec(), $table, $exec, $dump_file);

        $this->runAlterHandler($alter, $table);

        return $success;
    }

    /**
     * Verifies if the current operation context permits data alteration.
     * 
     * @return bool
     */
    private function isWritable(): bool
    {
        return in_array($this->access, [static::ADD_RECORD, static::UPDATE_RECORD]);
    }

    /**
     * Applies data validation declared via the #[Validate(...)] attribute on a property if available.
     *
     * @param string $name Property field name.
     * @param mixed $value Property structural payload value.
     * @return mixed Mapped sanitized runtime configuration values.
     */
    private function validateProperty(string $name, mixed $value): mixed
    {
        $attributes = (new \ReflectionProperty($this, $name))->getAttributes(Validate::class);
        if (!$attributes) return $value;

        $this->useAttribute($attributes[0], function (\ReflectionAttribute $attribute) use ($name, &$value) {
            $validator = new Validator;
            $input = [$name => $value];
            $validator->sanitize($input, [$name => $attribute->newInstance()->validator]);
            $value = $input[$name];
        });

        return $value;
    }

    /**
     * Hydrates the corresponding localized DataType configuration instance, casts values 
     * into active backend parameters, and attaches properties onto current entity frames.
     * 
     * @param string $name Target descriptor field name keys.
     * @param mixed $value Payload data store values.
     * @return void
     */
    private function assignTypedProperty(string $name, mixed $value): void
    {
        $type = $this->getPropertyType($name);

        if (!is_subclass_of($type, DataType::class)) return;

        $args = $this->getPropertyArgs($name);

        $property = new $type(...$args);
        $property->value = $property->toDatabase($value);

        if ($this->isPrimaryKeyProperty($name)) {
            $property->primary();
        }

        $this->{$name} = $property;
    }

    /**
     * Commits active target runtime alterations flags into tracking pools 
     * according to internal execution strategies.
     * 
     * @param string $name Target attribute field string identifier.
     * @return void
     */
    private function trackRecordChange(string $name): void
    {
        if ($this->access === static::ADD_RECORD) {
            $this->new_records[] = $name;
        } elseif ($this->access === static::UPDATE_RECORD) {
            $this->updated_records[] = $name;
        }
    }

    /**
     * Builds the specific SQL column statement mapping for all typed public properties.
     *
     * @return string[]
     */
    private function buildColumnDefinitions(): array
    {
        $definition = [];

        foreach ((new \ReflectionClass($this))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            $class = $this->getPropertyType($name);

            if (!is_subclass_of($class, DataType::class)) continue;

            $args = $this->getPropertyArgs($name);

            $default_value = $property->getDefaultValue();
            if (null !== $default_value) $args['default'] = $default_value;

            $args['nullable'] = $property->getType()->allowsNull();

            $type = new $class(...$args);

            if ($this->isPrimaryKeyProperty($name)) {
                $type->primary();
            }

            $definition[] = "`$name`" . $type->getData();
        }

        return $definition;
    }

    /**
     * Extracts configuration arguments from a property's #[Property(...)] attribute declaration.
     * 
     * @param string $name Property field name.
     * @return array
     */
    private function getPropertyArgs(string $name): array
    {
        $args = [];

        if ($attributes = (new \ReflectionProperty($this, $name))->getAttributes(Property::class)) {
            $this->useAttribute($attributes[0], function (\ReflectionAttribute $attribute) use (&$args) {
                $args = $attribute->newInstance()->args;
            });
        }

        return $args;
    }

    /**
     * Determines whether a specific public property features the explicit #[PrimaryKey] attribute.
     * 
     * @param string $name Property name.
     * @return bool
     */
    private function isPrimaryKeyProperty(string $name): bool
    {
        $isPrimary = false;

        if ($attributes = (new \ReflectionProperty($this, $name))->getAttributes(PrimaryKey::class)) {
            $this->useAttribute($attributes[0], function (\ReflectionAttribute $attribute) use (&$isPrimary) {
                if ($attribute->newInstance()) $isPrimary = true;
            });
        }

        return $isPrimary;
    }

    /**
     * Drops the associated entity mapping table from the storage engine.
     * 
     * @param ?bool $foreign_key_check Skips constraint validations if evaluation checks are cleared.
     * @return bool TRUE on success, FALSE otherwise.
     */
    public function drop(?bool $foreign_key_check = false) : bool
    {
        if ($foreign_key_check) \Clicalmani\Foundation\Support\Facades\DB::getInstance()->getPdo()->query('SET FOREIGN_KEY_CHECKS = 0');
        return with( new Maker($this->model->getTable()->name(), Maker::DROP_TABLE_IF_EXISTS) )->make();
    }

    /**
     * Custom programmatic schema alter operations runner wrapper.
     * Must be overriden in custom implementation child extensions.
     * 
     * @param \Clicalmani\Database\Factory\AlterOption $alter
     * @throws \Exception If not implemented by the descending implementation wrapper.
     * @return string
     */
    public function alter(AlterOption $alter) : string
    {
        throw new \Exception(
            sprintf("Method %s of class %s must be overriden.", 'alter', $this::class)
        );
    }

    /**
     * Lazy-loads row data attributes safely mapping current record identity states from the database.
     * 
     * @return void
     */
    protected function loadAttributes(): void
    {
        if ($this->model->isEmpty() || $this->attributesLoaded) {
            return;
        }
        
        /**
         * Construct standard dynamic validation search constraints 
         * to guarantee targeting the appropriate isolated backend state row.
         */
        $query = $this->model->getQuery();

        if ( !$query->getParam('where')) {
            $query->where(...$this->model->getKey()->toSqlCondition());
        }

        /**
         * Evaluate and bypass active parameters if the model utilizes soft delete protocols.
         */
        if (!!@class_uses($this->model)[\Clicalmani\Database\Traits\SoftDelete::class]) {
            $this->model->recycle();
        }
        
        $fields = [];
        foreach ((new \ReflectionClass($this))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!empty($property->getHooks())) continue;
            $fields[] = $property->getName();
        }
        
        $row = ($this->model)(implode(', ', array_map(fn($name) => "`$name`", $fields)))->first();
        
        if ($row) {
            foreach ($row as $name => $value) {
                /** @var class-string<DataType> */
                $dataTypeClass = $this->getPropertyType($name);
                
                /**
                 * Automatically type-cast values based on active configuration matrices 
                 * and apply explicit runtime formatters mapped from attributes.
                 */
                if ($attributes = (new \ReflectionProperty($this, $name))->getAttributes(Property::class)) {
                    $args = $attributes[0]->newInstance()->args;
                    $dataType = new $dataTypeClass(...$args);
                    
                    if (
                        false !== config('database.autoCast', false) &&                // Verify if auto-casting is not disabled globally
                        $this->model->autoCast() &&                                    // Verify if auto-casting is not disable on a specific model
                        !(isset($args['autoCast']) && $args['autoCast'] === false)     // Verify if auto-casting is not disabled on a specific attribute
                    ) $value = (new $dataType)->cast($value);

                    if ( method_exists($dataType, 'getFormatter') && $formatter = $dataType?->getFormatter()) {
                        $value = $this->{$formatter}($value);
                    }
                }
                
                $this->attributeValues[$name] = $value;
            }
        }
        
        $this->attributesLoaded = true;
    }

    /**
     * Compiles class-level explicit composite primary key strings (PRIMARY KEY (...)).
     *
     * @return string|null Mapped syntax constraint or null if omitted.
     */
    private function buildPrimaryKeyDefinition(): ?string
    {
        $attributes = (new \ReflectionClass($this))->getAttributes(PrimaryKey::class);
        if (!$attributes) return null;

        $definition = null;

        $this->useAttribute($attributes[0], function (\ReflectionAttribute $attribute) use (&$definition) {
            $keys = (array) $attribute->newInstance()->keys;
            $quoted = array_map(fn($key) => "`{$key}`", $keys);
            $definition = 'PRIMARY KEY (' . implode(', ', $quoted) . ')';
        });

        return $definition;
    }

    /**
     * Compiles all indexed structures and database keys declared via class attributes.
     *
     * @return string[]
     */
    private function buildIndexDefinitions(): array
    {
        $definition = [];

        $attributes = (new \ReflectionClass($this))->getAttributes(IndexType::class);

        foreach ($attributes as $attribute) {
            $this->useAttribute($attribute, function (\ReflectionAttribute $attribute) use (&$definition) {
                $instance = $attribute->newInstance();
                $index = new Index($instance->name);
                $index = $index->key(...explode(',', $instance->key));
                $index = $instance->unique ? $index->unique() : $index->index();

                $definition[] = $index->render();

                if ($instance->constraint) {
                    $definition[] = $this->buildForeignKeyDefinition($instance);
                }
            });
        }

        return $definition;
    }

    /**
     * Compiles detailed relational foreign key definitions.
     * 
     * @param object $instance Declarative Index metadata object context.
     * @return string Generated SQL string.
     */
    private function buildForeignKeyDefinition(object $instance): string
    {
        $index = (new Index(''))->constraint($instance->constraint);

        if ($instance->references) {
            $index = $index->foreignKey($instance->key)
                            ->references($instance->references['table'], $instance->references['key']);

            $index = $this->applyReferentialAction($index, 'onUpdate', $instance->onUpdate);
            $index = $this->applyReferentialAction($index, 'onDelete', $instance->onDelete);
        }

        return $index->render();
    }

    /**
     * Binds cascade constraints and behavioral updates to database relation keys.
     * 
     * @param Index $index Target Index instance.
     * @param string $type Operation target (onUpdate/onDelete).
     * @param string $action Target behavior command keyword.
     * @return Index
     */
    private function applyReferentialAction(Index $index, string $type, string $action): Index
    {
        $prefix = $type === 'onUpdate' ? 'onUpdate' : 'onDelete';

        return match ($action) {
            IndexType::ON_UPDATE_CASCADE, IndexType::ON_DELETE_CASCADE   => $index->{"{$prefix}Cascade"}(),
            IndexType::ON_UPDATE_RESTRICT, IndexType::ON_DELETE_RESTRICT => $index->{"{$prefix}Restrict"}(),
            IndexType::ON_UPDATE_SETNULL, IndexType::ON_DELETE_SETNULL   => $index->{"{$prefix}SetNull"}(),
            IndexType::ON_UPDATE_NOACTION, IndexType::ON_DELETE_NOACTION => $index->{"{$prefix}NoAction"}(),
            default => $index,
        };
    }

    /**
     * Maps global server configuration default character collections onto compiled migrations.
     * 
     * @param DBQuery $query Target compilation query context.
     * @return void
     */
    private function applyCollation(DBQuery $query): void
    {
        $db_config = require config_path('/database.php');
        $db_default = $db_config['connections'][$db_config['default']];

        if ($charset = @$db_default['charset']) {
            $collate = @$db_default['collation'] ?? "{$charset}_general_ci";
            $query->set('charset', $charset);
            $query->set('collate', $collate);
        }

        // Override si #[DefaultCollation(...)] est déclaré sur la classe
        if ($attributes = (new \ReflectionClass($this))->getAttributes(DefaultCollation::class)) {
            $this->useAttribute($attributes[0], function (\ReflectionAttribute $attribute) use ($query) {
                $instance = $attribute->newInstance();
                $query->set('charset', $instance->charset);
                $query->set('collate', $instance->collate);
            });
        }
    }

    /**
     * Sets up default engine requirements for physical storage allocation processing.
     * 
     * @param DBQuery $query
     * @return void
     */
    private function applyEngine(DBQuery $query): void
    {
        if ($attributes = (new \ReflectionClass($this))->getAttributes(Engine::class)) {
            $this->useAttribute($attributes[0], function (\ReflectionAttribute $attribute) use ($query) {
                $query->set('engine', $attribute->newInstance()->engine);
            });
            return;
        }

        $db_config = require config_path('/database.php');
        $db_default = $db_config['connections'][$db_config['default']];

        if ($engine = @$db_default['engine']) {
            $query->set('engine', $engine);
        }
    }

    /**
     * Intercepts and parses declarative structural dynamic options processing attributes.
     *
     * @param DBQuery $query Active framework execution pipeline context.
     * @param array $definition Active compiled database fields schema.
     * @return array{handler: ?string, definition: ?string} Mapped alteration hooks definitions tracker.
     */
    private function applyAlterOption(DBQuery $query, array &$definition): array
    {
        $result = ['handler' => null, 'definition' => null];

        $attributes = (new \ReflectionClass($this))->getAttributes(AlterOption::class);
        if (!$attributes) return $result;

        $this->useAttribute($attributes[0], function (\ReflectionAttribute $attribute) use ($query, &$definition, &$result) {
            $instance = $attribute->newInstance();
            $result['handler'] = $instance->handler;

            if (null === $result['handler']) {
                $query->set('type', DBQuery::ALTER);
                $definition = [$this->alter($instance)];
            } else {
                $result['definition'] = $this->alter($instance);
            }
        });

        return $result;
    }

    /**
     * Fires specific localized table mutation alter options context handlers.
     * 
     * @param array $alter Configured alter option metadata instructions.
     * @param string $table Active table reference targets.
     * @throws \PDOException If database operations run into technical connection faults.
     * @return void
     */
    private function runAlterHandler(array $alter, string $table): void
    {
        if (!$alter['handler'] || !$alter['definition']) return;

        try {
            $sql = 'ALTER TABLE ' . env('DB_TABLE_PREFIX', '') . $table . ' ' . $alter['definition'];
            $this->{$alter['handler']}($sql);
        } catch (\PDOException $e) {
            throw $e;
        }
    }

    /**
     * Deconstructs and invokes a callback onto an active ReflectionAttribute helper pipeline.
     * 
     * @param \ReflectionAttribute $attribute
     * @param callable $callback
     * @return void
     */
    private function useAttribute(\ReflectionAttribute $attribute, callable $callback)
    {
        $callback($attribute);
    }

    /**
     * Dispatches finalized raw SQL string components into persistent files or live execution pools.
     * 
     * @param \Clicalmani\Database\DBQueryBuilder $builder Compiled database query interface builder.
     * @param string $table Target entity catalog mapping name string identifier.
     * @param ?bool $exec Runs code on live environments directly if true.
     * @param ?string $dump_file Targets path destinations for custom export outputs.
     * @return bool
     */
    private function build(\Clicalmani\Database\DBQueryBuilder $builder, string $table, ?bool $exec = true, ?string $dump_file = null) : bool
    {
        /**
         * Execute the generated SQL statement.
         */
        if (TRUE == $exec) return $builder->status() === 'success';
        
        /** @var string */
        $sql = $builder->getSQL(); // Generated SQL statement
        /** @var string */
        $prefix = env('DB_TABLE_PREFIX', '');

        $sql = <<<SQL
        -- -----------------------------------------------------
        -- Table `$prefix{$table}`
        -- -----------------------------------------------------

        $sql;\n\n
        SQL;

        /**
         * No dump file specified
         */
        if (NULL === $dump_file) {
            echo $sql;
            return null;
        }

        /** @var resource */
        $fh = fopen(database_path("/manifests/$dump_file.sql"), 'a+');
        fwrite($fh, $sql);
        return fclose($fh);
    }

    /**
     * Magic structural invoker wrapping. Resolves list array matrices of internal attributes.
     * 
     * @return \Clicalmani\Database\Factory\Models\Attribute[]
     */
    public function __invoke() : array
    {
        return $this->getAttributes();
    }

    /**
     * Magic structural context getter override targeting entity properties.
     * 
     * @param string $name Attribute identifier reference string.
     * @return \Clicalmani\Database\Factory\Models\Attribute
     */
    public function __get(string $name) : Attribute
    {
        return $this->getAttribute($name);
    }

    /**
     * Magic verification checker routing hooks checking for properties in writing phases.
     * 
     * @param string $name
     * @return bool
     */
    public function __isset(string $name) : bool
    {
        return $this->isWriting($name);
    }

    /**
     * Magic structural content properties setter. Maps attributes into processing data stores.
     * 
     * @param string $name Property identifier name.
     * @param mixed $value Payload data value.
     * @return void
     */
    public function __set(string $name, mixed $value) : void
    {
        $this->setProperty($name, $value);
    }
}
