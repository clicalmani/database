<?php
namespace Clicalmani\Flesco\Database\Factory;

use Clicalmani\Flesco\TestUnits\Benchmark;

class Factory
{
    use NumberGenerator, 
        StringGenerator, 
        DateGenerator;

    /**
     * The name of the factory corresponding model.
     *
     * @var string Model class name
     */
    protected $model;

    /**
     * Holds the number of seed to execute
     * 
     * @var int Default 1
     */
    private $counter = 1;

    /**
     * Holds the overriden attributes
     * 
     * @var array Attributes to override
     */
    private $attributes_override = [];

    static function randomInt(int $min = 0, int $max = 1) : int 
    {
        return self::integer($min, $max);
    }

    static function randomFloat(int $min = 0, int $max = 1, int $decimal = 2) : float
    {
        return self::float($min, $max, $decimal);
    }

    static function randomName() : string
    {
        return self::name();
    }

    static function randomAlpha($length = 10) : string
    {
        return self::alpha($length);
    }

    static function randomAlphaNum($length = 10) : string
    {
        return self::alphaNum($length);
    }

    static function randomNum($length = 10) : string
    {
        return self::num($length);
    }

    static function randomDate(int $min_year = 1900, int $max_year = 2000) : string
    {
        return self::date($min_year, $max_year);
    }

    /**
     * Merges attributes
     * 
     * @param array $attributes [Optional] Attributes to merge to overriden attributes
     * @return array 
     */
    private function merge(array $attributes = []) : array
    {
        return array_merge($this->attributes_override, $attributes);
    }

    /**
     * Override attributes in the seed
     * 
     * @param array $attributes Only specified attributes will be overrided
     * @return array New seed
     */
    private function override($attributes = [])
    {
        $this->attributes_override = $this->merge($attributes);
        $seed = $this->definition();
        
        foreach ($this->attributes_override as $attribute => $value) {
            $seed[$attribute] = $value;
        }

        return array_unique( $seed );
    }

    /**
     * Create a factory from model
     * 
     * @param string $model Factory model
     * @return static
     */
    public static function fromModel(string $model) : static
    {
        global $root_path;

        /**
         * Factory is obtained by appending Factory to model class name
         */
        $factory = substr($model, strripos($model, '\\') + 1) . 'Factory';
        
        // Add namespace
        $factory_class = "\\Database\\Factories\\$factory";

        require_once $root_path . "\\database\\factories\\$factory.php";

        return new $factory_class;
    }

    /**
     * Override: Factory seed
     * 
     * @return array<string, mixed>
     */
    public function definition() : array
    {
        return [
            // Definition
        ];
    }

    /**
     * Allows to manipulate factory states
     * 
     * @param callable $callback A callable function that receive default attributes and return the 
     * attributes to override.
     * @return static
     */
    public function state(?callable $callback) : static
    {
        $this->override( $callback($this->definition()) );
        return $this;
    }

    /**
     * Manipulate multiple states at the same time
     * 
     * @param array $states Default []
     * @return static
     */
    public function states(array $states = []) : static
    {
        foreach ($states as $state) {
            $this->state($state);
        }

        return $this;
    }

    /**
     * Returns an instance of the factory
     * 
     * @return static
     */
    public static function new() : static
    {
        // Back trace the model class
        $model = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1]['class'];
        
        return static::fromModel($model);
    }

    /**
     * Repeat the seed operation n times.
     * 
     * @param int $num Counter
     * @return $this
     */
    public function count($num = 1) : static
    {
        $this->counter = $num;
        return $this;
    }

    /**
     * Start seeding
     * 
     * @return void
     */
    public function start($attributes = []) : void
    {
        $seeds = [];

        foreach (range(1, $this->counter) as $num) {
            $seeds[] = $this->override($attributes);
        }
        
        with (new $this->model)->insert($seeds);
    }
}
