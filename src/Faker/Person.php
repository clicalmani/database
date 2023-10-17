<?php
namespace Clicalmani\Database\Faker;

trait Person
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

    /**
     * Generate email
     * 
     * @return string
     */
    public static function email() : string 
    {
        return strtolower(self::name() . '.' . self::name()) . '@example.com';
    }

    /**
     * Phone number
     * 
     * @param string $format Format
     * @return string
     */
    public static function phone(string $format = '(+ddd) dd dd dd dd') : string 
    {
        while (str_contains($format, 'd')) {
            $format = preg_replace('/[d]/', self::num(1), $format, 1);
        }

        return $format;
    }

    /**
     * Personnal address
     * 
     * @param bool $short [Optional] Short address
     * @return string
     */
    public static function address(bool $short = true) : string 
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
}
