<?php 
namespace Clicalmani\Database\Factory;

class Sequence implements \Countable
{
    protected $sequence;

    public int $count;

    public int $index = 0;

    public function __construct( ...$sequence )
    {
        $this->sequence = $sequence;
        $this->count = sizeof($this->sequence);
    }

    public function count() : int
    {
        return $this->count;
    }

    public function __invoke()
    {
        return tap(nocall( $this->sequence[$this->index % $this->count] ), fn() => $this->index = $this->index + 1);
    }
}
