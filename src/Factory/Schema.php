<?php
namespace Clicalmani\Database\Factory;

use PHPUnit\Framework\Constraint\Callback;

/**
 * Class Schema
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Schema
{
    /**
     * Create table
     * 
     * @param string $table Table name
     * @param callable $callback Callback function
     * @return void
     */
    public static function create(string $table, callable $callback) : void
    {
        $callback(
            $maker = new Maker($table)
        );

        $maker->make();
    }

    /**
     * Create table if exists
     * 
     * @param string $table Table name
     * @param callable $callback A callback function
     * @return void
     */
    public static function dropBeforeCreate(string $table, callable $callback) : void
    {
        self::dropIfExists($table);
        self::create($table, $callback);
    }

    /**
     * Drop table
     * 
     * @param string $table Table name
     * @return void
     */
    public static function drop(string $table) : void
    {
        with( new Maker($table, MAKER::DROP_TABLE) )->make();
    }

    /**
     * Drop table if exists
     * 
     * @param string $table Table name
     * @return void
     */
    public static function dropIfExists(string $table) : void
    {
        with( new Maker($table, MAKER::DROP_TABLE_IF_EXISTS) )->make();
    }

    /**
     * Modify table
     * 
     * @param string $table Table name
     * @param callable $callback Callback function
     * @return void
     */
    public static function modify(string $table, callable $callback) : void
    {
        $callback(
            $maker = new Maker($table, MAKER::ALTER_TABLE)
        );

        $maker->make();
    }

    /**
     * Reverse data migration
     * 
     * @param string $migration
     * @return void
     */
    public static function reverse(string $migration) : void
    {
        tap(
            require database_path("/migrations/$migration.php"),
            fn($migrate) => $migrate->out()
        );
    }
}
