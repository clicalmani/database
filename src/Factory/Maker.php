<?php
namespace Clicalmani\Database\Factory;

use Clicalmani\Database\DBQuery;
use Clicalmani\Database\Factory\Column;
use Clicalmani\Database\Factory\Indexes\Index;
use Clicalmani\Database\Factory\AlterOption;

/**
 * Class Maker
 * 
 * @package Clicalmani\Database
 * @author clicalmani
 */
class Maker
{
    /**
     * Query object
     * 
     * @var \Clicalmani\Database\DBQuery
     */
    private $query;

    /**
     * Holds columns
     * 
     * @var ?array
     */
    private $columns = [];

    /**
     * Holds indexes
     * 
     * @var ?array
     */
    private $indexes = [];

    /**
     * Holds changes
     * 
     * @var ?array
     */
    private $changes = [];

    /**
     * Primary key
     * 
     * @var string
     */
    private $primary;

    /**
     * Class constances
     * 
     * |---------------------------------------------------
     * |                      Flags
     * |---------------------------------------------------
     */

    /**
     * Create table flag
     * 
     * @var int
     */
    const CREATE_TABLE         = DBQuery::CREATE;   

    /**
     * Drop table flag
     * 
     * @var int
     */
    const DROP_TABLE           = DBQuery::DROP_TABLE;            

    /**
     * Drop table if exists flag
     * 
     * @var int
     */
    const DROP_TABLE_IF_EXISTS = DBQuery::DROP_TABLE_IF_EXISTS;  

    /**
     * Alter table flag
     * 
     * @var int
     */
    const ALTER_TABLE          = DBQuery::ALTER;       

    protected static $current_alter_option;

    public function __construct(string $table, ?int $flag = self::CREATE_TABLE) 
    {
        $this->query = new DBQuery;
        $this->query->set('type', $flag);
        $this->query->set('table', $table);
    }

    /**
     * Create a table column
     * 
     * @param string $name Column name
     * @return \Clicalmani\Database\Factory\Column
     */
    public function column(string $name) : \Clicalmani\Database\Factory\Column
    {
        $column = new Column($name);
        $this->columns[] = $column;

        return $column;
    }

    /**
     * Alter a database table
     * 
     * @return \Clicalmani\Database\Factory\AlterOption
     */
    public function alter() : AlterOption
    {
        if (static::$current_alter_option) $this->changes[] = static::$current_alter_option;
        return tap(new AlterOption, fn(AlterOption $option) => $this->changes[] = $option);
    }

    /**
     * Create a table index
     * 
     * @param string $name Index name
     * @return \Clicalmani\Database\Factory\Indexes\Index
     */
    public function index(string $name = '') : Index
    {
        return tap(new Index($name), fn(Index $index) => $this->indexes[] = $index);
    }

    /**
     * Table creation engine
     * 
     * @param string $engine
     * @return void
     */
    public function engine(string $engine = 'InnoDB') : void
    {
        $this->query->set('engine', $engine);
    }

    /**
     * Table default collation
     * 
     * @param ?string $default_collation
     * @return void
     */
    public function collate(?string $default_collation = 'utf8mb4_unicode_ci') : void
    {
        $this->query->set('collate', $default_collation);
    }

    /**
     * Table default character set
     * 
     * @param ?string $default_charset
     * @return void
     */
    public function charset(?string $default_charset = 'utf8mb4') : void
    {
        $this->query->set('charset', $default_charset);
    }

    /**
     * Table primary key
     * 
     * @param string ...$attributes
     * @return void
     */
    public function primaryKey(string ...$attributes) : void
    {
        $value = '';

        foreach ($attributes as $index => $key) {
            if ($index < count($attributes) - 1) $value .= '`' . $key . '`, ';
            else $value .= '`' . $key . '`';
        }

        $this->primary = 'PRIMARY KEY (' . $value . ')';
    }

    /**
     * Make migration
     * 
     * @return bool True on success, false on failure
     */
    public function make() : bool
    {
        $definition = [];

        foreach ($this->columns as $column) {
            $definition[] = $column->render();
        }

        if ($this->primary) $definition[] = $this->primary;

        if ($this->indexes) {
            foreach ($this->indexes as $index) {
                $definition[] = $index->render();
            }
        }

        if ($definition) $this->query->set('definition', $definition);

        $changes = [];

        foreach ($this->changes as $change) {
            $changes[] = $change->render();
        }

        if ($changes) $this->query->set('definition', $changes);
        
        return $this->query->exec()->status() === 'success';
    }
}
