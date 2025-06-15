<?php
namespace Clicalmani\Database\Factory;

interface FactoryInterface
{
    /**
     * Override: Factory seed
     * 
     * @return array<string, mixed>
     */
    public function definition() : array;

    /**
     * Manipulate factory states
     * 
     * @param callable $callback A callable function that receive default attributes and return the 
     * attributes to override.
     * @return \Clicalmani\Database\Factory\FactoryInterface
     */
    public function state(?callable $callback) : \Clicalmani\Database\Factory\FactoryInterface;

    /**
     * Manipulate multiple states at the same time
     * 
     * @param Sequence $states
     * @return \Clicalmani\Database\Factory\FactoryInterface
     */
    public function states(Sequence $seqs) : \Clicalmani\Database\Factory\FactoryInterface;

    /**
     * Returns an instance of the factory
     * 
     * @return \Clicalmani\Database\Factory\FactoryInterface
     */
    public static function new() : \Clicalmani\Database\Factory\FactoryInterface;

    /**
     * Repeat the seed operation n times.
     * 
     * @param int $num Counter
     * @return \Clicalmani\Database\Factory\FactoryInterface
     */
    public function count($num = 1) : \Clicalmani\Database\Factory\FactoryInterface;

    /**
     * Start seeding
     * 
     * @return void
     */
    public function make($attributes = []) : void;
}