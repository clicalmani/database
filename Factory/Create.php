<?php
namespace Clicalmani\Database\Factory;

use Clicalmani\Database\DBQueryBuilder;
use Clicalmani\Database\DBQueryIterator;
use Clicalmani\Database\Factory\DataTypes\DataType;
use Clicalmani\Exceptions\DataTypeException;

class Create extends DBQueryBuilder implements \IteratorAggregate 
{
	private $dataType;

	public function __construct(
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

	public function query() : void
	{
	    $result = $this->db->query($this->sql);
    		
		$this->status     = $result ? true: false;
	    $this->error_code = $this->db->errno();
	    $this->error_msg  = $this->db->error();
	}
	
	public function getIterator() : \Traversable
	{
		return new DBQueryIterator($this);
	}
}
