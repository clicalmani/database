<?php
namespace Clicalmani\Database\Faker;

trait LoremIpsum 
{
    public function __construct(private $lipsum = null)
    {
        $this->lipsum = new \joshtronic\LoremIpsum;
    }

    public static function word()
    {

    }
}
