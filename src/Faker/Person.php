<?php
namespace Clicalmani\Database\Faker;

/**
 * Class Person
 * 
 * Generate person informations
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
trait Person
{
    /**
     * Person name generator
     * 
     * @return string Generated name
     */
    public static function name() : string
    {
        $names = json_decode( file_get_contents( __DIR__ . '/data/names.json') ) ?? [];
        return @ $names[self::integer(0, count($names) - 1)];
    }

    /**
     * Generate email
     * 
     * @param ?string $host
     * @return string
     */
    public static function email(?string $host = 'exemple.com') : string 
    {
        return strtolower(self::name() . '.' . self::name()) . "@$host";
    }

    /**
     * Phone number
     * 
     * @param ?string $format Format
     * @return string
     */
    public static function phone(?string $format = '(+ddd) dd dd dd dd') : string 
    {
        while (str_contains($format, 'd')) {
            $format = preg_replace('/[d]/', self::num(1), $format, 1);
        }

        return $format;
    }

    /**
     * Personnal address
     * 
     * @param ?bool $short Short address
     * @return string
     */
    public static function address(?bool $short = true) : string 
    {
        $xdt = xdt();
        $xdt->setDirectory( __DIR__ . '/data' );
        $xdt->connect('addresses', true, true);

        $addresses = $xdt->select('address');
        $address = $addresses->pos(self::integer(0, $addresses->length - 1));

        $ret = '';

        foreach ($address->children() as $key => $addrLine) {
            $addrLine = $xdt->parse($addrLine);
            $ret .= ' ' . $addrLine->val();
            if ($short && $key == 1) break;
        }

        $xdt->close();

        return $ret;
    }

    /**
     * Generate a random person
     * 
     * @return array
     */
    public static function person() : array
    {
        return [
            'name' => self::name(),
            'email' => self::email(),
            'phone' => self::phone(),
            'address' => self::address()
        ];
    }
}
