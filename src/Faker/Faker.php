<?php 
namespace Clicalmani\Database\Faker;

/**
 * Class Faker
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Faker 
{
    use NumberGenerator, 
        StringGenerator, 
        DateGenerator,
        Person,
        LoremIpsum,
        Places;

    /**
     * Generate a random integer between $min and $max.
     * 
     * @param int $min
     * @param int $max
     * @return int
     */
    public static function randomInt(int $min = 0, int $max = 1) : int 
    {
        return self::integer($min, $max);
    }

    /**
     * Generate a random float between $min and $max.
     * 
     * @param int $min
     * @param int $max
     * @return float
     */
    public static function randomFloat(int $min = 0, int $max = 1, int $decimal = 2) : float
    {
        return self::float($min, $max, $decimal);
    }

    /**
     * Generate a random person name.
     * 
     * @return string Person name
     */
    public static function randomName() : string
    {
        return self::name();
    }

    /**
     * Generate a random string.
     * 
     * @param int $length
     * @return string
     */
    public static function randomAlpha(?int $length = 10) : string
    {
        return self::alpha($length);
    }

    /**
     * Generate a random alphanumeric value.
     * 
     * @param ?int $length
     * @return string
     */
    public static function randomAlphaNum($length = 10) : string
    {
        return self::alphaNum($length);
    }

    /**
     * Generate a random numeric value.
     * 
     * @param ?int $length
     * @return string
     */
    public static function randomNum(?int $length = 10) : string
    {
        return self::num($length);
    }

    /**
     * Generate a random date between $min_year and $max_year.
     * 
     * @param ?int $min_year
     * @param ?int $max_year
     * @return string
     */
    public static function randomDate(int $min_year = 1900, int $max_year = 2000) : string
    {
        return self::date($min_year, $max_year);
    }
}
