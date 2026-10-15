<?php
namespace Clicalmani\Database\Concerns;

final class Raw
{
    public function __construct(private string $expression) {}
 
    public function getValue(): string
    {
        return $this->expression;
    }
 
    public function __toString(): string
    {
        return $this->expression;
    }
}