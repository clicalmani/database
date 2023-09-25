<?php 
namespace Clicalmani\Flesco\Database\Factory;

class Sequence implements \Countable
{
    public int $index = 0;

    public function __construct(private mixed $sequences = null)
    {
        
    }

    public function nextValue()
    {
        if ( is_array($this->sequences) ) {
            $this->index++;
            return $this->sequences[$this->index];
        }
        if ( is_callable($this->sequences) ) return call_user_func($this->sequences, new Sequence);
    }

	public function count() : int { return sizeof($this->sequences); }
}
