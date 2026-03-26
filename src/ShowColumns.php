<?php
namespace Clicalmani\Database;

use Clicalmani\Foundation\Support\Facades\DB;

/**
 * Class Lock
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class ShowColumns extends DBQueryBuilder implements \IteratorAggregate 
{
	public function __construct(
		protected $params = array(), 
		protected $options = []
	) 
	{ 
		parent::__construct($params, $options);

        $table = DB::getPrefix() . $this->params['table'] ?? '';
        $this->sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . env('DB_NAME', '') . "' AND TABLE_NAME = '$table'";
	}
	
	/**
	 * (non-PHPdoc)
	 * @overriden
	 * @return void
	 */
	public function query() : void
	{
		/** @var \PDOStatement */
	    $statement = DB::query($this->sql, $this->options, $this->params['options']);
		
		$this->dispatch('query');
    	
		$this->status     = $statement ? true: false;
	    $this->error_code = DB::errno();
	    $this->error_msg  = DB::error();
		$this->num_rows   = DB::numRows($statement);
		
	    while ($row = DB::fetch($statement, \PDO::FETCH_OBJ)) {
	    	$this->result->add($row);
		}
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
