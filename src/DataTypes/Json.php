<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;

class Json extends DataType
{
    private array $config;

    public function __construct(mixed ...$parameters)
    {
        $this->json();

        if (TRUE === @ $parameters['nullable']) $this->nullable();
        else $this->nullable(false);

        if ($comment = @ $parameters['comment']) $this->comment($comment);

        $this->config = \Clicalmani\Foundation\Support\Facades\Config::app();
    }

    /**
     * Returns the JSON representation of a value
     * 
     * @param mixed $value
     * @return string|false
     */
    public function encode(mixed $value) : string|false
    {
        return json_encode($value, $this->config['json']['encode']['flags'], $this->config['json']['encode']['depth']);
    }

    /**
     * Decodes a JSON string
     * 
     * @param string $json
     * @return mixed
     */
    public function decode(string $json) : mixed
    {
        return json_decode($json, $this->config['json']['decode']['associative'], $this->config['json']['decode']['depth'], $this->config['json']['decode']['flags']);
    }
}
