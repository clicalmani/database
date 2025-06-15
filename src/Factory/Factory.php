<?php
namespace Clicalmani\Database\Factory;

/**
 * Class Factory
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Factory implements FactoryInterface
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

    public function definition() : array
    {
        return [
            // Definition
        ];
    }

    public function state(?callable $callback) : static
    {
        $this->override( $callback($this->definition()) );
        return $this;
    }

    public function states(Sequence $seqs) : static
    {
        foreach (range(1, $seqs->count) as $num) {  
            $this->state($seqs());
        }

        return $this;
    }

    public static function new() : static
    {
        $factory = get_called_class();
        return new $factory;
    }

    public function count($num = 1) : static
    {
        $this->counter = $num;
        return $this;
    }

    public function make($attributes = []) : void
    {
        $seeds = [];

        foreach (range(1, $this->counter) as $num) {
            $seeds[] = $this->override($attributes);
        }
        
        if ( $this->model ) with (new $this->model)->insert($seeds);
    }

    public function faker()
    {
        return new \Clicalmani\Database\Faker\Faker;
    }

    public function sequence()
    {
        return new Sequence;
    }
}
