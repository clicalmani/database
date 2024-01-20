<?php
namespace Clicalmani\Database\Faker;

/**
 * Class NumberGenerator
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
trait NumberGenerator
{
    /**
     * Integer generator
     * 
     * @param ?int $min
     * @param ?int $max
     * @return int
     */
    public static function integer(?int $min = 0, ?int $max = 1) : int
    {
        return random_int($min, $max);
    }

    /**
     * Float generator
     * 
     * @param ?int $min
     * @param ?int $max
     * @param ?int $decimal Decimal place
     * @return float
     */
    public static function float(?int $min = 0, ?int $max = 1, ?int $decimal = 2) : float
    {
        $decimal_part = '00';

        if ( $decimal ) {
            $decimal_part = self::integer((int) str_pad('1', $decimal, '0'), (int) str_pad('9', $decimal, '9'));
        }

        return (float) self::integer($min, $max) . '.' . $decimal_part;
    }
}
