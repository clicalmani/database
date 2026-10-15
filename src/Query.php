<?php
namespace Clicalmani\Database;

class Query
{
    public function __construct(
        public ?string $sql = null, 
        public array $bindings = [], 
        public float $duration = 0.0,
        public array $profile = [],
        public string $connection = 'default')
    {
        $this->sql = $sql;
        $this->bindings = $bindings;
        $this->profile = $profile;
        $this->duration = $duration;
        $this->connection = $connection;
    }
}