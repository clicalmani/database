<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Foundation\Collection\Collection;
use Clicalmani\Foundation\Collection\CollectionInterface;

abstract class Relationship implements \JsonSerializable
{
    protected Elegant $model;
    protected ModelInterface|CollectionInterface|null $result = null;
    protected array|\Closure|null $default = null;

    abstract public function get(): mixed;

    public function jsonSerialize(): mixed
    {
        return $this->get();
    }
}