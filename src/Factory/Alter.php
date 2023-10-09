<?php
namespace Clicalmani\Database\Factory;

use Clicalmani\Database\DBQueryBuilder;
use Clicalmani\Database\DBQueryIterator;

class Alter extends DBQueryBuilder implements \IteratorAggregate 
{
	public function __construct(
		protected $params = array(), 
		protected $options = []
	) 
    { 
		parent::__construct($params, $options);
		
		$this->sql .= 'ALTER TABLE ' . $this->db->getPrefix() . $this->params['table'];
		
		if (isset($this->params['definition'])) {
            $this->sql .= ' ' . join(',', $this->params['definition']) . ' ';
		}
		
		if (isset($this->params['engine'])) $this->sql .= 'ENGINE = ' . $this->params['engine'];

        if (isset($this->params['collate'])) $this->sql .= 'DEFAULT COLLATE = ' . $this->params['collate'];

        if (isset($this->params['charset'])) $this->sql .= 'DEFAULT CHARACTER SET = ' . $this->params['charset'];
	}

	public function query() : void
	{
	    $result = $this->db->execute($this->sql);
    		
		$this->status     = $result ? true: false;
	    $this->error_code = $this->db->errno();
	    $this->error_msg  = $this->db->error();
	}
	
	public function getIterator() : \Traversable
	{
		return new DBQueryIterator($this);
	}
}
