<?php 
namespace Clicalmani\Flesco\Database\Seeders;

/**
 * Database seeder class
 * 
 * @package Clicalmani\Flesco
 * @author clicalmani
 */
abstract class Seeder 
{
    /**
     * Run a database seed
     * 
     * @return void
     */
    abstract public function run() : void;
}
