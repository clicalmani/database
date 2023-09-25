<?php
namespace Clicalmani\Flesco\Database\Factory;

trait NumberGenerator
{
    static function integer(int $min = 0, int $max = 1) : int
    {
        return random_int($min, $max);
    }

    static function float(int $min = 0, int $max = 1, int $decimal = 2) : float
    {
        $number = self::integer($min, $max) . '.' . self::integer();

        $decimal_part = '00';

        if ( $decimal ) {
            $decimal_part = self::integer((int) str_pad('1', $decimal, '0'), (int) str_pad('9', $decimal, '9'));
        }

        return (float) self::integer($min, $max) . '.' . $decimal_part;
    }
}
