<?php
namespace Clicalmani\Database;

use Clicalmani\Foundation\Support\Facades\DB;

/**
 * Class Select
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Select extends DBQueryBuilder implements \IteratorAggregate 
{
	public function __construct(
		protected $params = array(), 
		protected $options = []
	) 
	{ 
		parent::__construct($params, $options);
		
		$this->sql = 'SELECT ';
		
		if (isset($this->params['distinct']) AND $this->params['distinct'] === false) $this->sql = 'SELECT ';
		else $this->sql = 'SELECT DISTINCT ';
		
		if (isset($this->params['calc']) AND $this->params['calc']) $this->sql .= 'SQL_CALC_FOUND_ROWS ';
		else $this->sql .= '';
		
		if (isset($this->params['fields'])) {
			$this->sql .= $this->params['fields'];
		} else {
			$this->sql .= '*';
		}
		
		$this->sql .= ' FROM ' . join(',', $this->sanitizeTables($this->params['tables'])) . ' ';
		
		if (isset($this->params['join'])) {
			
			foreach ($this->params['join'] as $joint) {
				
				$this->sql .= $this->addJoint($joint) . ' ';
			}
		}

		$this->sql .= ' WHERE';
		
		$this->sql .= match ($this->params['recycle']) {
			1 => ' deleted_at IS NULL',
			2 => ' deleted_at IS NOT NULL',
			default => ' TRUE'
		};
		
		if (isset($this->params['where'])) {
			$this->sql .= ' AND ' . $this->params['where'];
		}

		if (isset($this->params['group_by'])) {
				
			$this->sql .=' GROUP BY ' . $this->params['group_by'];
			
			if (isset($this->params['having'])) {
		
				$this->sql .= ' HAVING ' . $this->params['having'];
			}
		}
		
		if (isset($this->params['order_by'])) {
			
			$this->sql .= ' ORDER BY ' . $this->params['order_by'];
		}
		
		if ( isset($this->params['limit']) ) $this->sql .= ' LIMIT ' . $this->params['offset'] . ', ' . $this->params['limit'];
	}
	
	/**
	 * (non-PHPdoc)
	 * @overriden
	 * @return void
	 */
	public function query() :void
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
