<?php
namespace Clicalmani\Database\Faker;

/**
 * Trait Places
 * 
 * Generate place address
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
trait Places 
{
    /**
     * XDT Object
     * 
     * @var \Clicalmani\XPower\XDT
     * @see https://github.com/clicalmani/xpower
     */
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

    /**
     * Generate a random country name
     * 
     * @return string
     */
    public static function country() : string
    {
        $countries = self::countries();
        $names = explode(',', $countries->pos(self::integer(0, $countries->length - 1))?->attr('name'));
        return $names[0];
    }

    /**
     * Generate a random city name
     * 
     * @param string $country [Optional] If specified generate a city of the specified country
     * @return string
     */
    public static function city(?string $country = null) : string
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

    /**
     * Generate a latitude
     * 
     * @return string
     */
    public static function lat() : string
    {
        $arr = explode(',', self::latlng());
        return $arr[0];
    }

    /**
     * Generate a longitude
     * 
     * @return string
     */
    public static function lon()
    {
        $arr = explode(',', self::latlng());
        return end($arr);
    }

    /**
     * Generate a random place
     * 
     * @return array
     */
    public static function place() : array
    {
        return [
            'country' => self::country(),
            'city' => self::city(),
            'address' => self::address(),
            'lat' => self::lat(),
            'lon' => self::lon()
        ];
    }

    /**
     * Generate a random place name
     * 
     * @return string
     */
    public static function placeName() : string
    {
        return self::city() . ', ' . self::country();
    }

    /**
     * Generate a random place address
     * 
     * @return string
     */
    public static function placeAddress() : string
    {
        return self::address() . ', ' . self::city() . ', ' . self::country();
    }

    /**
     * Generate a random place address with latitude and longitude
     * 
     * @return string
     */
    public static function placeAddressLatLng() : string
    {
        return self::address() . ', ' . self::city() . ', ' . self::country() . ' (' . self::lat() . ', ' . self::lon() . ')';
    }
}
