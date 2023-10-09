<?php
namespace Clicalmani\Database\Faker;

trait NameGenerator
{
    /**
     * Person name generator
     * 
     * @return string Generated name
     */
    static function name() : string
    {
        $names = json_decode( file_get_contents( __DIR__ . '/data/names.json') );
        $index = self::integer(0, count($names) - 1);

        return $names[$index];
    }
}
