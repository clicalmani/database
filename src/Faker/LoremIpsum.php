<?php
namespace Clicalmani\Database\Faker;

/**
 * Trait LoremIpsum
 * 
 * Generate Lorem Ipsum
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
trait LoremIpsum 
{
    /**
     * Lipsum object
     * 
     * @var \joshtronic\LoremIpsum
     * @see https://github.com/joshtronic/php-loremipsum
     */
    private static $lipsum;

    public function __construct()
    {
        self::$lipsum = new \joshtronic\LoremIpsum;
    }

    /**
     * Lorem Ipsum: generate words
     * 
     * @param ?int $count Number of words to be generated
     * @return string 
     */
    public static function word(?int $count = 1) : string
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

    /**
     * Lorem Ipsum: generate sentences
     * 
     * @param ?int $count Number of sentences to be generated
     * @return string
     */
    public static function sentence(?int $count = 1) : string
    {
        return self::$lipsum->sentences($count);
    }

    /**
     * Lorem Ipsum: generate paragraph
     * 
     * @param ?int $count Number of paragraphs to be generated
     * @return string
     */
    public static function paragraph(?int $count = 1) : string
    {
        return self::$lipsum->paragraphs($count);
    }
}
