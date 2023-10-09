<?php
namespace Clicalmani\Database\Factory;

use Clicalmani\Database\DBQuery;
use Clicalmani\Database\Factory\Column;
use Clicalmani\Database\Factory\Indexes\Index;
use Clicalmani\Database\Factory\AlterOption;

/**
 * Maker
 * 
 * @package Clicalmani\Database
 * @author clicalmani
 */
class Maker
{
    private $query;
    private $columns = [];
    private $indexes = [];
    private $changes = [];
    private $primary;

    const CREATE_TABLE         = DBQuery::CREATE;
    const DROP_TABLE           = DBQuery::DROP_TABLE;
    const DROP_TABLE_IF_EXISTS = DBQuery::DROP_TABLE_IF_EXISTS;
    const ALTER_TABLE          = DBQuery::ALTER;

    static $current_alter_option;

    public function __construct(string $table, int $flag = self::CREATE_TABLE) 
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

        $option = new AlterOption;
        $this->changes[] = $option;

        return $option;
    }

    /**
     * Create a table index
     * 
     * @param string $name Index name
     * @return \Clicalmani\Database\Factory\Indexes\Index
     */
    public function index(string $name = '') : Index
    {
        $index = new Index($name);
        $this->indexes[] = $index;
        return $index;
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
     * @param string $default_collation
     * @return void
     */
    public function collate(string $default_collation) : void
    {
        $this->query->set('collate', $default_collation);
    }

    /**
     * Table default character set
     * 
     * @param string $default_charset
     * @return void
     */
    public function charset(string $default_charset) : void
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
