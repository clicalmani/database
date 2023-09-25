<?php
namespace Clicalmani\Flesco\Database\Factory;

trait DateGenerator
{
    static function date(int $min_year = 1900, int $max_year = 2000) : string
    {
        return self::integer($min_year, $max_year) . '-' . 
               str_pad(self::integer(1, 12), 2, '0', STR_PAD_LEFT) . '-' . 
               str_pad(self::integer(1, 31), 2, '0', STR_PAD_LEFT);
    }
}
