<?php
namespace Clicalmani\Database;

class Query
{
    public function __construct(
        public ?string $sql = null, 
        public array $bindings = [], 
        public array $profile = [])
    {
        $this->sql = $sql;
        $this->bindings = $bindings;
        $this->profile = $profile;
    }
}