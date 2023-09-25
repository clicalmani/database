<?php
namespace Clicalmani\Flesco\Database;

class DBQueryIterator implements \Iterator 
{
  
	private $obj;
	
	function __construct($obj)
	{
		$this->obj = $obj;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::rewind()
	 */
	function rewind() : void
	{
		$this->obj->setKey(0);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::key()
	 */
	function key() : mixed
	{ 
		return $this->obj->key();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::current()
	 */
	function current() : mixed { return $this->obj->getRow(); }
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::next()
	 */
	function next() : void
	{
		$this->obj->setKey($this->obj->key()+1);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::valid()
	 */
	function valid() : bool
	{
		return $this->obj->key() < $this->obj->numRows();
	}
}
?>