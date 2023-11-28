<?php
namespace Clicalmani\Database\Factory;

class Schema
{
    static function create($table, $callback)
    {
        $callback(
            $maker = new Maker($table)
        );

        $maker->make();
    }

    static function dropBeforeCreate($table, $callback)
    {
        self::dropIfExists($table);
        self::create($table, $callback);
    }

    static function drop($table)
    {
        with( new Maker($table, MAKER::DROP_TABLE) )->make();
    }

    static function dropIfExists($table)
    {
        with( new Maker($table, MAKER::DROP_TABLE_IF_EXISTS) )->make();
    }

    static function modify($table, $callback)
    {
        $callback(
            $maker = new Maker($table, MAKER::ALTER_TABLE)
        );

        $maker->make();
    }

    public static function reverse(string $filename)
    {
        tap(
            require database_path("/migrations/$filename.php"),
            fn($migrate) => $migrate->out()
        );
    }
}
