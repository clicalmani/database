<?php
namespace Clicalmani\Database\Factory\Models;

final class Table
{
    private function __construct(private string $name)
    {
        $this->name = $name;
    }

    public function alias(): string
    {
        @ [$name, $alias] = explode(' ', $this->name);
        return $alias ? $alias: env('DB_TABLE_PREFIX') . $name;
    }

    public function alised(): bool
    {
        return !!$this->alias();
    }

    public function name(): string
    {
        return explode(' ', $this->name)[0];
    }

    public function withAlias(): string
    {
        return $this->name;
    }

    public static function from(string $name): self
    {
        return new self($name);
    }

    public function __toString()
    {
        return $this->name;
    }
}