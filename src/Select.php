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
		
	    while ($row = DB::fetch($statement, \PDO::FETCH_ASSOC)) {
	    	$this->result->add($row);
		}
	}

	private function paginer(int $range, int $limit) {
		extract(DB::fetch(DB::query('SELECT FOUND_ROWS() AS num_rows'), \PDO::FETCH_ASSOC));
		$GLOBALS['num_rows'] = $num_rows;
		$page_links = '<div>';
		
		if($num_rows > $limit) {
			if(isset($_GET['page'])) {
				$page = $_GET['page'];
			} else {
				$page = 1;
			}
			
			$currpage = $_SERVER['PHP_SELF']; 

			$currpage = str_replace("?page=$page", '', $currpage);
			
			if($page == 1) {
				$page_links .= '&laquo; PREV';
			} else {
				$page_links .= "<a href='" . $currpage . "'&page=' . ($page - 1) . '>&laquo; PREV</a>";
			}
			
			$num_of_pages = ceil($num_rows/$limit);
			
			$lrange = max(1, $page - (($range - 1) / 2));
			$rrange = min($num_of_pages, $page + (($range - 1) / 2));
	
	
			if(($rrange - $lrange) < ($range - 1)) {
				if($lrange == 1) {
					$rrange = min($lrange + ($range - 1), $num_of_pages);
				} else {
					$lrange = max($rrange - ($range - 1), 0);
				}
			}
			
			if($lrange > 1) {
				$page_links .= "<a href='" . $currpage . "?page=1'>1</a>..";
			} else {
				$page_links .= '&nbsp;&nbsp;';
			}
			
			for($i = 1; $i <= $num_of_pages; $i++) {
				if($i == $page) {
					$page_links .= $i;
				} else {
					if($lrange <= $i && $i <= $rrange) {
						$page_links .= "<a href='" . $currpage . "?page=$i'>" . $i . "</a>";
					}
				}
			}
			
			if($rrange < $num_of_pages) {
				$page_links .= "..<a href='" . $currpage . "?page=$num_of_pages'>" . $num_of_pages . "</a>";
			} else {
				$page_links .= '&nbsp;&nbsp;';
			}
			
			if(($num_rows - ($limit * $page)) > 0) {
				$page_links .= "<a href='" . $currpage . "?page=" . ($page + 1) . "'>NEXT &raquo;</a>";
			} else {
				$page_links .= 'NEXT &raquo;';
			}
		} else {
			$page_links .= '&laquo; PREV&nbsp;&nbsp;1&nbsp;&nbsp;NEXT &raquo;&nbsp;&nbsp;';
		}
		
		return $page_links .= "</div>";
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
