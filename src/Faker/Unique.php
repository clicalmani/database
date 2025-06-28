<?php
namespace Clicalmani\Database\Faker;

class Unique
{
    /**
     * @var array
     */
    private static array $generatedIds = [];

    /**
     * @var int
     */
    private int $maxRetries;

    public function __construct(int $maxRetries = 1000)
    {
        $this->maxRetries = $maxRetries;
    }

    private function safeEmail()
    {
        return faker()->email();
    }

    private function safeName()
    {
        return faker()->name();
    }

    private function safeAlpha()
    {
        return faker()->alpha();
    }

    private function safeUserName()
    {
        return faker()->name() . faker()->num(self::integer(3, 5));
    }

    public function __get(string $name)
    {
        $attempts = 0;

        do {
            if ($attempts >= $this->maxRetries) {
                throw new \Exception("Unable to generate a unique value after $this->maxRetries attempts");
            }

            $value = $this->{$name}();
            $attempts++;
        } while (in_array($value, self::$generatedIds));

        self::$generatedIds[] = $value;

        return $value;
    }
}