<?php
namespace Clicalmani\Database\DataTypes;

use Clicalmani\Database\Factory\DataTypes\DataType;
use Override;

class Json extends DataType
{
    private array $config;

    public function __construct(mixed ...$options)
    {
        $this->json();

        if (TRUE === @ $options['nullable']) $this->nullable();
        else $this->nullable(false);

        parent::sharedOptions($options);

        $this->config = \Clicalmani\Core\Support\Facades\Config::app();
    }

    #[Override]
    public function cast(mixed $value): mixed
    {
        return is_string($value) ? json_decode($value, $this->config['json']['decode']['associative'], $this->config['json']['decode']['depth'], $this->config['json']['decode']['flags']): $value;
    }

    #[Override]
    public function toDatabase($value): mixed
    {
        return $value ? json_encode($value, $this->config['json']['encode']['flags'], $this->config['json']['encode']['depth']): null;
    }
}
