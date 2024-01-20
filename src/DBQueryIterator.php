<?php
namespace Clicalmani\Database;

/**
 * DBQuery iterator
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class DBQueryIterator implements \Iterator 
{
	/**
	 * Constructor
	 * 
	 * @param \Clicalmani\Database\DBQueryBuilder $obj
	 */
	public function __construct(private DBQueryBuilder $obj) {}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::rewind()
	 */
	public function rewind() : void
	{
		$this->obj->setKey(0);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::key()
	 */
	public function key() : mixed
	{ 
		return $this->obj->key();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::current()
	 */
	public function current() : mixed { return $this->obj->getRow(); }
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::next()
	 */
	public function next() : void
	{
		$this->obj->setKey($this->obj->key()+1);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::valid()
	 */
	public function valid() : bool
	{
		return $this->obj->key() < $this->obj->numRows();
	}
}
?>