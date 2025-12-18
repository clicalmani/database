<?php
namespace Clicalmani\Database;

use Clicalmani\Foundation\Support\Facades\DB;

/**
 * Class Unlock
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Union extends DBQueryBuilder implements \IteratorAggregate 
{
    private DBQueryBuilder $builder1;
    private DBQueryBuilder $builder2;

	public function __construct(
		protected $params1 = array(), 
		protected $options1 = [],
        protected $params2 = array(), 
		protected $options2 = [],
        protected bool $all = false
	) 
	{ 
        parent::__construct([], []);
	}
	
	/**
	 * (non-PHPdoc)
	 * @overriden
	 * @return void
	 */
	public function query() : void
	{
        $this->sql = $this->builder1->getSQL() . ($this->all ? ' UNION ALL ': ' UNION ') . $this->builder2->getSQL();

		$statement = DB::query(
			$this->sql,
			array_merge($this->options1, $this->options2),
			array_merge(@$this->params1['options'] ?? [], @$this->params2['options'] ?? [])
		);

		$this->dispatch('query');
		
		$this->status     = $statement ? true: false;
	    $this->error_code = DB::errno();
	    $this->error_msg  = DB::error();
		$this->num_rows   = DB::numRows($statement);

        while ($row = DB::fetch($statement, \PDO::FETCH_ASSOC)) {
	    	$this->result->add($row);
		}
	}

    public function setFields(string $fields = '*')
    {
        $this->params1['fields'] = $fields;
        $this->params2['fields'] = $fields;
        $this->builder1 = new Select($this->params1, $this->options1);
        $this->builder2 = new Select($this->params2, $this->options2);
    }
	
	/**
	 * Get iterator
	 * 
	 * @return \Traversable
	 */
	public function getIterator() : \Traversable
	{
		return new DBQueryIterator($this);
	}
}
