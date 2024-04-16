<?php
namespace Clicalmani\Database\Factory;

/**
 * Class Factory
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Factory
{
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
    
    /**
     * Merges attributes
     * 
     * @param array $attributes [Optional] Attributes to merge to overriden attributes
     * @return array 
     */
    private function merge(?array $attributes = []) : array
    {
        return array_merge($this->attributes_override, $attributes);
    }

    /**
     * Override attributes in the seed
     * 
     * @param array $attributes Only specified attributes will be overriden
     * @return array New seed
     */
    private function override(?array $attributes = [])
    {
        $this->attributes_override = $this->merge($attributes);
        $seed = $this->definition();
        
        foreach ($this->attributes_override as $attribute => $value) {
            $seed[$attribute] = ($value instanceof Sequence) ? call( $value ): $value;
        }
        
        return $seed;
    }

    /**
     * Create a factory from model
     * 
     * @param string $model Factory model
     * @return static
     */
    public static function fromModel(string $model) : static
    {
        /**
         * Factory is obtained by appending Factory to model class name
         */
        $factory = substr($model, strripos($model, '\\') + 1) . 'Factory';
        
        // Add namespace
        $factory_class = "\\Database\\Factories\\$factory";
            
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
     * Manipulate factory states
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
     * @param Sequence $states
     * @return static
     */
    public function states(Sequence $states) : static
    {
        foreach (range(1, $states->count) as $num) {  
            $this->state($states());
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
    public function make($attributes = []) : void
    {
        $seeds = [];

        foreach (range(1, $this->counter) as $num) {
            $seeds[] = $this->override($attributes);
        }
        
        with (new $this->model)->insert($seeds);
    }

    public function faker()
    {
        return new \Clicalmani\Database\Faker\Faker;
    }
}
