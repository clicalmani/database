<?php
namespace Clicalmani\Flesco\Database\Factory;

trait StringGenerator
{
    static function name() : string
    {
        $names = json_decode( file_get_contents( dirname( dirname( __DIR__ ) ) . '/data/names.json') );
        $index = self::integer(0, count($names) - 1);

        return $names[$index];
    }

    static function alpha($length = 10) : string
    {
        $str = 'abcdefghijklmnopqrstuvwxyz';
        $str = str_pad($str, $length, $str);
        return substr(str_shuffle( $str ), 0, $length);
    }

    static function alphaNum($length = 10) : string
    {
        return substr(str_shuffle(md5(microtime())), 0, $length);
    }

    static function num($length = 10) : string
    {
        $str = '0123456789';
        $str = str_pad($str, $length, $str);
        return substr(str_shuffle( $str ), 0, $length);
    }
}
