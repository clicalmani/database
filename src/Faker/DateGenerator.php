<?php
namespace Clicalmani\Database\Faker;

/**
 * Trait DateGenerator
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
trait DateGenerator
{
    /**
     * Date generator
     * 
     * @param int $min_year
     * @param int $max_year
     * @return string
     */
    public static function date(int $min_year = 1900, int $max_year = 2000) : string
    {
        return self::integer($min_year, $max_year) . '-' . 
               str_pad(self::integer(1, 12), 2, '0', STR_PAD_LEFT) . '-' . 
               str_pad(self::integer(1, 31), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Date time generator
     * 
     * @param int $min_year
     * @param int $max_year
     * @return string
     */
    public static function dateTime(int $min_year = 1900, int $max_year = 2000) : string
    {
        return self::integer($min_year, $max_year) . '-' . 
               str_pad(self::integer(1, 12), 2, '0', STR_PAD_LEFT) . '-' . 
               str_pad(self::integer(1, 31), 2, '0', STR_PAD_LEFT) . ' ' . 
               str_pad(self::integer(0, 23), 2, '0', STR_PAD_LEFT) . ':' . 
               str_pad(self::integer(0, 59), 2, '0', STR_PAD_LEFT) . ':' . 
               str_pad(self::integer(0, 59), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Time generator
     * 
     * @param bool $with_seconds Add seconds
     * @return string
     */
    public static function time(bool $with_seconds = true) : string
    {
        $time = str_pad(self::integer(0, 23), 2, '0', STR_PAD_LEFT) . ':' . 
                str_pad(self::integer(0, 59), 2, '0', STR_PAD_LEFT);

        if ($with_seconds) $time .= ':' . str_pad(self::integer(0, 59), 2, '0', STR_PAD_LEFT);
        
        return $time;
    }

    /**
     * Year generator
     * 
     * @param int $min_year
     * @param int $max_year
     * @return int
     */
    public static function year(int $min_year = 1900, int $max_year = 2050) : int
    {
        return self::integer($min_year, $max_year);
    }

    /**
     * Month generator
     * 
     * @return int
     */
    public static function month() : int
    {
        return self::integer(1, 12);
    }

    /**
     * Day generator
     * 
     * @return int
     */
    public static function day() : int
    {
        return self::integer(1, 31);
    }

    /**
     * Hour generator
     * 
     * @return int
     */
    public static function hour() : int
    {
        return self::integer(0, 23);
    }

    /**
     * Minute generator
     * 
     * @return int
     */
    public static function minute() : int
    {
        return self::integer(0, 59);
    }

    /**
     * Second generator
     * 
     * @return int
     */
    public static function second() : int
    {
        return self::integer(0, 59);
    }

    /**
     * Timestamp generator
     * 
     * @param int $min_year
     * @param int $max_year
     * @return int
     */
    public static function timestamp(int $min_year = 1900, int $max_year = 2000) : int
    {
        return strtotime(self::dateTime($min_year, $max_year));
    }

    /**
     * Timezone generator
     * 
     * @return string
     */
    public static function timezone() : string
    {
        return self::randomElement([
            'Africa/Abidjan', 'Africa/Accra', 'Africa/Addis_Ababa', 'Africa/Algiers', 'Africa/Asmara', 'Africa/Bamako', 'Africa/Bangui', 'Africa/Banjul', 'Africa/Bissau', 'Africa/Blantyre', 'Africa/Brazzaville', 'Africa/Bujumbura', 'Africa/Cairo', 'Africa/Casablanca', 'Africa/Ceuta', 'Africa/Conakry', 'Africa/Dakar', 'Africa/Dar_es_Salaam', 'Africa/Djibouti', 'Africa/Douala', 'Africa/El_Aaiun', 'Africa/Freetown', 'Africa/Gaborone', 'Africa/Harare', 'Africa/Johannesburg', 'Africa/Juba', 'Africa/Kampala', 'Africa/Khartoum', 'Africa/Kigali', 'Africa/Kinshasa', 'Africa/Lagos', 'Africa/Libreville', 'Africa/Lome', 'Africa/Luanda', 'Africa/Lubumbashi', 'Africa/Lusaka', 'Africa/Malabo', 'Africa/Maputo', 'Africa/Maseru', 'Africa/Mbabane', 'Africa/Mogadishu', 'Africa/Monrovia', 'Africa/Nairobi', 'Africa/Ndjamena', 'Africa/Niamey', 'Africa/Nouakchott', 'Africa/Ouagadougou', 'Africa/Porto-Novo', 'Africa/Sao_Tome', 'Africa/Tripoli', 'Africa/Tunis', 'Africa/Windhoek', 'America/Adak', 'America/Anchorage', 'America/Anguilla', 'America/Antigua', 'America/Araguaina', 'America/Argentina/Buenos_Aires', 'America/Argentina/Catamarca', 'America/Argentina/Cordoba', 'America/Argentina/Jujuy', 'America/Argentina/La_Rioja', 'America/Argentina/Mendoza', 'America/Argentina/Rio_Gallegos', 'America/Argentina/Salta', 'America/Argentina/San_Juan', 'America/Argentina/San_Luis', 'America/Argentina/Tucuman', 'America/Argentina/Ushuaia', 'America/Aruba', 'America/Asuncion', 'America/Atikokan', 'America/Bahia', 'America/Bahia_Banderas', 'America/Barbados', 'America/Belem', 'America/Belize', 'America/Blanc-Sablon', 'America/Boa_Vista', 'America/Bogota', 'America/Boise', 'America/Cambridge_Bay', 'America/Campo_Grande', 'America/Cancun', 'America/Caracas', 'America/Cayenne', 'America/Cayman', 'America/Chicago', 'America/Chihuahua', 'America/Costa_Rica', 'America/Creston', 'America/Cuiaba', 'America/Curacao', 'America/Danmarkshavn', 'America/Dawson', 'America/Dawson_Creek', 'America/Denver', 'America/Detroit', 'America/Dominica', 'America/Edmonton', 'America/Eirunepe', 'America/El_Salvador', 'America/Fort_Nelson', 'America/Fortaleza', 'America/Glace_Bay', 'America/Godthab', 'America/Goose_Bay', 'America/Grand_Turk', 'America/Grenada', 'America/Guadeloupe', 'America/Guatemala', 'America/Guayaquil', 'America/Guyana', 'America/Halifax', 'America/Havana', 'America/Hermosillo', 'America/Indiana/Indianapolis', 'America/Indiana/Knox', 'America/Indiana/Marengo', 'America/Indiana/Petersburg', 'America/Indiana/Tell_City', 'America/Indiana/Vevay', 'America/Indiana/Vincennes', 'America/Indiana/Winamac', 'America/Inuvik', 'America/Iqaluit', 'America/Jamaica', 'America/Juneau', 'America/Kentucky/Louisville', 'America/Kentucky/Monticello', 'America/Kralendijk', 'America/La_Paz', 'America/Lima', 'America/Los_Angeles', 'America/Lower_Princes'
        ]);
    }
}
