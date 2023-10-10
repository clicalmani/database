<?php
namespace Clicalmani\Database\Faker;

trait Places 
{
    private static $xdt;

    private static function xdt()
    {
        $xdt = xdt();
        $xdt->setDirectory( __DIR__ . '/data' );
        return $xdt;
    }

    private static function countries()
    {
        $xdt = self::xdt();
        $xdt->connect('countries', true, true);
        return $xdt->select('country');
    }

    public static function country() : string
    {
        $countries = self::countries();
        $names = explode(',', $countries->pos(self::integer(0, $countries->length - 1))?->attr('name'));
        return $names[0];
    }

    public static function city(string $country = null) : string
    {
        $xdt = self::xdt();
        $xdt->connect('cities', true, true);

        if ($country) {
            $cities = $xdt->select('countryregion[name="' . $country . '"] > state > city');
            if ($cities->length === 0) $cities = $xdt->select('countryregion > state > city');
        } else $cities = $xdt->select('countryregion > state > city');

        return $cities->pos(self::integer(0, $cities->length - 1))->attr('name');
    }

    private static function latlng()
    {
        $countries = self::countries();
        return $countries->pos(self::integer(0, $countries->length - 1))?->attr('latlng');
    }

    public static function lat()
    {
        $arr = explode(',', self::latlng());
        return $arr[0];
    }

    public static function lon()
    {
        $arr = explode(',', self::latlng());
        return end($arr);
    }
}
