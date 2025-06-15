<?php
namespace Clicalmani\Database\Factory\Models;

trait SQLAggregate
{
    public function count(string $field = '*') : int
    {
        return $this->query->count($field);
    }

    public function avg(string $field) : float
    {
        return $this->query->avg($field);
    }

    public function sum(string $field) : float
    {
        return $this->query->sum($field);
    }

    public function min(string $field) : float
    {
        return $this->query->min($field);
    }

    public function max(string $field) : float
    {
        return $this->query->max($field);
    }
}