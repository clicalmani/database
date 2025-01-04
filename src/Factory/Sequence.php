<?php 
namespace Clicalmani\Database\Factory;

/**
 * Class Sequence
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Sequence implements \Countable
{
    /**
     * Sequences
     * 
     * @var array
     */
    protected $sequence;

    /**
     * Count sequences
     * 
     * @var int
     */
    public int $count;

    /**
     * Current sequence index
     * 
     * @var int
     */
    public int $index = 0;

    /**
     * Constructor
     * 
     * @param mixed $sequence
     */
    public function __construct(mixed ...$sequence)
    {
        $this->sequence = $sequence;
        $this->count = sizeof($this->sequence);
    }

    /**
     * Count sequence
     * 
     * @return int
     */
    public function count() : int
    {
        return $this->count;
    }
    
    public function __invoke() : mixed
    {
        return tap(nocall( $this->sequence[$this->index % $this->count] ), fn() => $this->index = $this->index + 1);
    }

    /**
     * Get the next sequence
     * 
     * @return mixed
     */
    public function next() : mixed
    {
        return $this->__invoke();
    }
}
