<?php 
namespace Clicalmani\Database\Faker;

class Faker 
{
    use NumberGenerator, 
        StringGenerator, 
        DateGenerator,
        Person,
        LoremIpsum,
        Places;

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
}