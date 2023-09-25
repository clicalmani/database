<?php
namespace Clicalmani\Flesco\Database\Factory;

use Clicalmani\Flesco\Database\DBQueryBuilder;
use Clicalmani\Flesco\Database\Factory\DataTypes\DataType;
use Clicalmani\Flesco\Exceptions\DataTypeException;

class Create extends DBQueryBuilder implements \IteratorAggregate 
{
	private $dataType;

	function __construct(
		protected $params = array(), 
		protected $options = []
	) 
    { 
		parent::__construct($params, $options);
		
		$this->sql .= 'CREATE TABLE IF NOT EXISTS ' . $this->db->getPrefix() . $this->params['table'];
		
		if (isset($this->params['definition'])) {
            $this->sql .= ' (' . join(',', $this->params['definition']) . ') ';
		}
		
		if (isset($this->params['engine'])) $this->sql .= 'ENGINE = ' . $this->params['engine'];

        if (isset($this->params['collate'])) $this->sql .= 'DEFAULT COLLATE = ' . $this->params['collate'];

        if (isset($this->params['charset'])) $this->sql .= 'DEFAULT CHARACTER SET = ' . $this->params['charset'];
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
