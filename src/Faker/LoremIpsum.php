<?php
namespace Clicalmani\Database\Faker;

trait LoremIpsum 
{
    private static $lipsum;

    public function __construct()
    {
        self::$lipsum = new \joshtronic\LoremIpsum;
    }

    public static function word(int $count = 1)
    {
        $words = self::$lipsum->words(max($count, 100), false, true);
        
        $tmp = [];
        $keys = array_rand($words, $count);
        
        if ($count === 1) $keys = [$keys];
        
        foreach ($keys as $key) {
            $tmp[] = $words[$key];
        }

        return join(' ', $tmp);
    }

    public static function sentence(int $count = 1)
    {
        return self::$lipsum->sentences($count);
    }

    public static function paragraph(int $count)
    {
        return self::$lipsum->paragraphs($count);
    }
}
