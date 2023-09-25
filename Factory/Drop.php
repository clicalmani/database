<?php
namespace Clicalmani\Flesco\Database\Factory;

use Clicalmani\Flesco\Database\DBQueryBuilder;

class Drop extends DBQueryBuilder implements \IteratorAggregate 
{
	function __construct(
		protected $params = array(), 
		protected $options = []
	) 
    { 
		parent::__construct($params, $options);
		
		$this->sql .= 'DROP TABLE ' . (isset($this->params['exists']) ? 'IF EXISTS ': '') . $this->db->getPrefix() . $this->params['table'];
	}

	function query() 
	{
	    $result = $this->db->query($this->sql);
    		
		$this->status     = $result ? true: false;
	    $this->error_code = $this->db->errno();
	    $this->error_msg  = $this->db->error();
	}
	
	function getIterator() : \Traversable
	{
		return new DBQueryIterator($this);
	}
	
	function error() { parent::error(); }
}
