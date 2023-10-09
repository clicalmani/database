<?php
namespace Clicalmani\Database\Faker;

trait StringGenerator
{
    /**
     * Characters generator
     * 
     * @param int $length Default 10
     * @return string
     */
    static function alpha(int $length = 10) : string
    {
        $str = 'abcdefghijklmnopqrstuvwxyz';
        $str = str_pad($str, $length, $str);
        return substr(str_shuffle( $str ), 0, $length);
    }

    /**
     * Alpha numeric generator
     * 
     * @param int $length Default 10
     * @return string
     */
    static function alphaNum(int $length = 10) : string
    {
        return substr(str_shuffle(md5(microtime())), 0, $length);
    }

    /**
     * Numbers generator
     * 
     * @param int $length Default 10
     * @return string
     */
    static function num(int $length = 10) : string
    {
        $str = '0123456789';
        $str = str_pad($str, $length, $str);
        return substr(str_shuffle( $str ), 0, $length);
    }

    /**
     * Generate email
     * 
     * @return string
     */
    public static function email() : string 
    {
        return strtolower(self::name() . '.' . self::name()) . '@example.com';
    }
}
