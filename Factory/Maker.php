<?php
namespace Clicalmani\Flesco\Database\Factory;

use Clicalmani\Flesco\Database\DBQuery;
use Clicalmani\Flesco\Database\Factory\Column;
use Clicalmani\Flesco\Database\Factory\Indexes\Index;
use Clicalmani\Flesco\Database\Factory\AlterOption;

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

    function __construct($table, $flag = self::CREATE_TABLE) 
    {
        $this->query = new DBQuery;
        $this->query->set('type', $flag);
        $this->query->set('table', $table);
    }

    function column($name)
    {
        $column = new Column($name);
        $this->columns[] = $column;

        return $column;
    }

    function alter()
    {
        if (static::$current_alter_option) $this->changes[] = static::$current_alter_option;

        $option = new AlterOption;
        $this->changes[] = $option;

        return $option;
    }

    function index($name)
    {
        $index = new Index($name);
        $this->indexes[] = $index;
        return $index;
    }

    function engine($engine = 'InnoDB')
    {
        $this->query->set('engine', $engine);
    }

    function collate($default_collation)
    {
        $this->query->set('collate', $default_collation);
    }

    function charset($default_charset)
    {
        $this->query->set('charset', $default_charset);
    }

    function primaryKey(...$keys)
    {
        $value = '';

        foreach ($keys as $index => $key) {
            if ($index < count($keys) - 1) $value .= '`' . $key . '`, ';
            else $value .= '`' . $key . '`';
        }

        $this->primary = 'PRIMARY KEY (' . $value . ')';
    }

    function make()
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
