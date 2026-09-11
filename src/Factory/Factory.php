<?php
namespace Clicalmani\Database\Factory;

/**
 * Class Factory
 * 
 * Provides database factory functionality for seeding and generating model instances.
 * 
 * @package Clicalmani\Database\Factory
 * @author clicalmani
 */
class Factory implements FactoryInterface
{
    /**
     * The target model class associated with the factory.
     *
     * @var string|null Model class name
     */
    protected $model;

    /**
     * Number of model instances or seeds to generate.
     * 
     * @var int Default is 1
     */
    private int $counter = 1;

    /**
     * Attribute overrides applied to the default model definition.
     * 
     * @var array Attributes to override
     */
    private array $attributes_override = [];
    
    /**
     * Merges custom attributes into the existing attribute overrides.
     * 
     * @param array|null $attributes Attributes to merge with existing overrides.
     * @return array Combined attributes array.
     */
    private function merge(?array $attributes = []): array
    {
        return array_merge($this->attributes_override, $attributes ?? []);
    }

    /**
     * Applies attribute overrides to the default factory definition.
     * 
     * @param array|null $attributes Attribute key-value pairs to override.
     * @return array Evaluated seed dataset.
     */
    private function override(?array $attributes = []): array
    {
        $this->attributes_override = $this->merge($attributes);
        $seed = $this->definition();
        
        foreach ($this->attributes_override as $attribute => $value) {
            $seed[$attribute] = ($value instanceof Sequence) ? call($value) : $value;
        }
        
        return $seed;
    }

    /**
     * Defines the default model attribute state.
     * 
     * @return array Model attribute structure.
     */
    public function definition(): array
    {
        return [
            // Model attribute definitions
        ];
    }

    /**
     * Applies a state transformation callback to override default attributes.
     * 
     * @param callable|null $callback Transformation callback returning modified attributes.
     * @return static Current factory instance.
     */
    public function state(?callable $callback): static
    {
        if ($callback) {
            $this->override($callback($this->definition()));
        }
        
        return $this;
    }

    /**
     * Applies a sequence of state transformations across multiple iterations.
     * 
     * @param Sequence $seqs Sequence instance holding transformation callbacks.
     * @return static Current factory instance.
     */
    public function states(Sequence $seqs): static
    {
        foreach (range(1, $seqs->count) as $num) {  
            $this->state($seqs());
        }

        return $this;
    }

    /**
     * Instantiates a new factory instance for the called child class.
     * 
     * @return static New factory instance.
     */
    public static function new(): static
    {
        $factory = static::class;
        return new $factory;
    }

    /**
     * Sets the number of seeds or models to generate.
     * 
     * @param int $num Count of records to generate.
     * @return static Current factory instance.
     */
    public function count($num = 1): static
    {
        $this->counter = $num;
        return $this;
    }

    /**
     * Generates seeds and inserts them directly into the underlying model storage.
     * 
     * @param array $attributes Additional attribute overrides.
     * @return void
     */
    public function make($attributes = []): void
    {
        $seeds = [];

        foreach (range(1, $this->counter) as $num) {
            $seeds[] = $this->override($attributes);
        }
        
        if ($this->model) {
            with(new $this->model)->insert($seeds);
        }
    }

    /**
     * Creates and returns a new Faker instance for generating dummy data.
     * 
     * @return \Clicalmani\Database\Faker\Faker
     */
    public function faker(): \Clicalmani\Database\Faker\Faker
    {
        return new \Clicalmani\Database\Faker\Faker;
    }

    /**
     * Creates and returns a new Sequence instance.
     * 
     * @return Sequence
     */
    public function sequence(): Sequence
    {
        return new Sequence;
    }
}