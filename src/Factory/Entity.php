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
use Clicalmani\Database\Factory\Models\Model;
use Clicalmani\Fundation\Support\Facades\Log;
use Clicalmani\Fundation\Validation\InputValidator;

abstract class Entity 
{
    /**
     * Reading mode
     * 
     * @var int
     */
    const READ_RECORD = 0;

    /**
     * Update writing mode
     * 
     * @var int
     */
    const UPDATE_RECORD = 1;

    /**
     * Insert writing mode
     * 
     * @var int
     */
    const ADD_RECORD = 2;

    /**
     * Entity model
     * 
     * @var \Clicalmani\Database\Factory\Models\Model
     */
    protected \Clicalmani\Database\Factory\Models\Model $model;

    /**
     * Entity access mode
     * 
     * @var int
     */
    protected $access;

    /**
     * Inserted records
     * 
     * @var string[]
     */
    protected array $new_records = [];

    /**
     * Updated records
     * 
     * @var string[]
     */
    protected array $updated_records = [];

    /**
     * Get entity attributes
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
            $value = $property->isInitialized($this) ? $property->getValue($this): null;

            $attribute = new Attribute($name, $value);
            $attribute->model = $this->model;
            $attribute->access = $this->access;
            
            $ret[] = $attribute;
        }

        return $ret;
    }

    /**
     * Get attribute by name
     * 
     * @param string $name Attribute name
     * @return \Clicalmani\Database\Factory\Models\Attribute
     */
    public function getAttribute(string $name) : Attribute
    {
        return tap(new Attribute($name), function(Attribute $attribute) {
            $attribute->model = $this->model;
            $attribute->value = $attribute->isCustom() ? $attribute->getCustomValue(): $this->model->get("`$attribute->name`");
            $attribute->model = $this->model;
            $attribute->access = $this->access;
        });
    }

    /**
     * Property access getter
     * 
     * @return int
     */
    public function getAccess() : int
    {
        return $this->access;
    }

    /**
     * Property access setter
     * 
     * @param int $access
     * @return void
     */
    public function setAccess(int $access) : void
    {
        $this->access = $access;
    }

    /**
     * Model getter
     * 
     * @return \Clicalmani\Database\Factory\Models\Model
     */
    public function getModel() : Model
    {
        return $this->model;
    }

    /**
     * Model setter
     * 
     * @param \Clicalmani\Database\Factory\Models\Model $model
     * @return void
     */
    public function setModel(Model $model) : void
    {
        $this->model = $model;
    }

    /**
     * A wrapper method to set a public property value.
     * 
     * @param string $name Property name
     * @param mixed $value Property value
     * @return void
     */
    public function setProperty(string $name, mixed $value) : void
    {
        $attr = new Attribute($name, $value);
        $attr->model = $this->model;

        /**
         * Avoid setting custom property 
         * Only set field on insert and update
         */
        if (FALSE === $attr->isCustom() && in_array($this->access, [static::ADD_RECORD, static::UPDATE_RECORD])) {
            
            try {
                /**
                 * Validate property
                 */
                if ($attributes = (new \ReflectionProperty($this, $name))->getAttributes(Validate::class)) {
                    $attribute = $attributes[0];
                    $this->useAttribute($attribute, function(\ReflectionAttribute $attribute) use($name, &$value) {
                        $validator = new InputValidator;
                        $input = [$name => $value];
                        $validator->sanitize($input, [$name => $attribute->newInstance()->validator]);
                        $validator->passed($name);
                        $value = $input[$name];
                    });
                }

                $type = $this->getPropertyType($name);
                $args = [];

                // Whether property is a primary key
                $is_primary_key = false;

                /**
                 * Property attribute
                 * 
                 * Apply user defined property attributes
                 */
                if ($attributes = (new \ReflectionProperty($this, $name))->getAttributes(Property::class)) {
                    $this->useAttribute($attributes[0], function(\ReflectionAttribute $attribute) use(&$args) {
                        $args = $attribute->newInstance()->args;
                    });
                }

                /**
                 * Primary key
                 */
                if ($attributes = (new \ReflectionProperty($this, $name))->getAttributes(PrimaryKey::class)) {
                    $attribute = $attributes[0];
                    $this->useAttribute($attribute, function(\ReflectionAttribute $attribute) use(&$is_primary_key) {
                        $is_primary_key = true;
                    });
                }

                if ( is_subclass_of($type, DataType::class) ) {

                    $property = new $type( ...$args );

                    if ($type === \Clicalmani\Database\DataTypes\Json::class) {
                        $value = $property->encode($value);
                    }

                    $property->value = $value;
                    
                    if (TRUE === $is_primary_key) $property->primary();
                    
                    $this->{$name} = $property;
                }
            } catch (\ReflectionException $e) {
                Log::error($e->getMessage(), E_ERROR, __CLASS__, __LINE__);
            } catch (\Exception $e) {
                Log::error($e->getMessage(), E_ERROR, __CLASS__, __LINE__);
            }
            
            if ( $this->access === static::ADD_RECORD ) $this->new_records[] = $name;
            if ( $this->access === static::UPDATE_RECORD ) $this->updated_records[] = $name;
        }
        
    }

    public function getPropertyType(string $name)
    {
        $property = ( new \ReflectionProperty($this, $name) );

        if (property_exists($this, $name) && $property->hasType()) {
            return $property->getType()->getName();
        }
        
        throw new \Exception();
    }

    /**
     * Verify if attribute is in writing mode
     * 
     * @param string $name Attribute name
     * @return bool TRUE on success, FALSE on failure.
     */
    public function isWriting(string $name) : bool
    {
        return in_array($name, $this->new_records);
    }

    /**
     * Verify if attribute is in updating mode
     * 
     * @param string $name Attribute name
     * @return bool TRUE on success, FALSE on failure.
     */
    public function isUpdating(string $name) : bool
    {
        return in_array($name, $this->updated_records);
    }

    /**
     * Migrate entity
     * 
     * @param ?bool $exec
     * @param ?string $dump_file
     * @return bool TRUE on success, FALSE otherwise.
     */
    public function migrate(?bool $exec = true, ?string $dump_file = null) : bool
    {
        $table = $this->model->getTable();

        $query = new DBQuery;
        $query->set('type', DBQuery::CREATE);
        $query->set('table', $table);

        $definition = [];

        foreach (( new \ReflectionClass($this) )->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            $class = $this->getPropertyType($name);

            if ( is_subclass_of($class, DataType::class) ) {

                $args = [];

                /**
                 * Property attribute
                 * 
                 * Apply user defined property attributes
                 */
                if ($attributes = (new \ReflectionProperty($this, $name))->getAttributes(Property::class)) {
                    $this->useAttribute($attributes[0], function(\ReflectionAttribute $attribute) use(&$args) {
                        $args = $attribute->newInstance()->args;
                    });
                }

                $type = new $class( ...$args );

                /**
                 * Primary key
                 */
                if ($attributes = (new \ReflectionProperty($this, $name))->getAttributes(PrimaryKey::class)) {
                    $this->useAttribute($attributes[0], function(\ReflectionAttribute $attribute) use($type) {
                        if ($attribute->newInstance()) $type->primary();
                    });
                }

                $definition[] = "`$name`" . $type->getData();
            }
        }

        /**
         * Primary key
         */
        if ($attributes = (new \ReflectionClass($this))->getAttributes(PrimaryKey::class)) {

            $this->useAttribute($attributes[0], function(\ReflectionAttribute $attribute) use(&$definition) {

                $keys = (array) $attribute->newInstance()->keys;

                $value = '';

                foreach ($keys as $index => $key) {
                    if ($index < count($keys) - 1) $value .= '`' . $key . '`, ';
                    else $value .= '`' . $key . '`';
                }

                $definition[] = 'PRIMARY KEY (' . $value . ')';
            });
        }

        /**
         * Index keys
         */
        if ($attributes = (new \ReflectionClass($this))->getAttributes(IndexType::class)) {
            foreach ($attributes as $attribute) {
                $this->useAttribute($attribute, function(\ReflectionAttribute $attribute) use(&$definition) {
                    $instance =  $attribute->newInstance();
                    $index = new Index($instance->name);
                    $index = $index->key(...explode(',', $instance->key));

                    if ($instance->unique) $index = $index->unique();
                    else $index = $index->index();

                    $definition[] = $index->render();

                    if ($instance->constraint) {
                        $index = new Index('');
                        $index = $index->constraint($instance->constraint);

                        if ($instance->references) {
                            $reference_table = $instance->references['table'];
                            $reference_key = $instance->references['key'];
                            $index = $index->foreignKey($instance->key)->references($reference_table, $reference_key);

                            switch($instance->onUpdate) {
                                case IndexType::ON_UPDATE_CASCADE: $index = $index->onUpdateCascade(); break;
                                case IndexType::ON_UPDATE_RESTRICT: $index = $index->onUpdateRestrict(); break;
                                case IndexType::ON_UPDATE_SETNULL: $index = $index->onUpdateSetNull(); break;
                                case IndexType::ON_UPDATE_NOACTION: $index = $index->onUpdateNoAction(); break;
                            }

                            switch($instance->onDelete) {
                                case IndexType::ON_DELETE_CASCADE: $index = $index->onDeleteCascade(); break;
                                case IndexType::ON_DELETE_RESTRICT: $index = $index->onDeleteRestrict(); break;
                                case IndexType::ON_DELETE_SETNULL: $index = $index->onDeleteSetNull(); break;
                                case IndexType::ON_DELETE_NOACTION: $index = $index->onDeleteNoAction(); break;
                            }
                        }

                        $definition[] = $index->render();
                    }
                });
            }
        }

        /**
         * Table default collation
         */
        $db_config = require config_path( '/database.php' );
        $db_default = $db_config['connections'][$db_config['default']];

        if ($charset = @$db_default['charset']) {

            $collate = @$db_default['collation'] ?? "{$charset}_general_ci";

            $query->set('charset', $charset);
            $query->set('collate', $collate);
        }

        if ($engine = @$db_default['engine']) $query->set('engine', $engine);

        /**
         * Default Collation
         */
        if ($attributes = (new \ReflectionClass($this))->getAttributes(DefaultCollation::class)) {
            $this->useAttribute($attributes[0], function(\ReflectionAttribute $attribute) use($query) {
                $instance = $attribute->newInstance();
                $query->set('charset', $instance->charset);
                $query->set('collate', $instance->collate);
            });
        }

        /**
         * Alter
         */
        if ($attributes = (new \ReflectionClass($this))->getAttributes(AlterOption::class)) {
            $this->useAttribute($attributes[0], function(\ReflectionAttribute $attribute) use($query, &$definition) {
                $query->set('type', DBQuery::ALTER);
                $definition = [$this->alter($attribute->newInstance())];
            });
        }
        
        $query->set('definition', $definition);

        return $this->build($query->exec(), $table, $exec, $dump_file);
    }

    /**
     * Drop entity
     * 
     * @return bool TRUE on success, FALSE otherwise.
     */
    public function drop() : bool
    {
        return with( new Maker($this->model->getTable(), Maker::DROP_TABLE_IF_EXISTS) )->make();
    }

    /**
     * Alter entity
     * 
     * Must be overriden
     * 
     * @param \Clicalmani\Database\Factory\AlterOption $alter
     * @return string
     */
    public function alter(AlterOption $alter) : string
    {
        throw new \Exception(
            sprintf("Method alter() of class %s must be overriden.", $this::class)
        );
    }

    private function useAttribute(\ReflectionAttribute $attribute, callable $callback)
    {
        $callback($attribute);
    }

    /**
     * Execute or output the generated SQL statement.
     * 
     * @param \Clicalmani\Database\DBQueryBuilder $builder
     * @param string $table Table name
     * @param ?bool $exec
     * @param ?string $dump_file 
     * @return mixed
     */
    private function build(\Clicalmani\Database\DBQueryBuilder $builder, string $table, ?bool $exec = true, ?string $dump_file = null) : mixed
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
        $fh = fopen(database_path("/migrations/$dump_file.sql"), 'a+');
        fwrite($fh, $sql);
        return fclose($fh);
    }
}
