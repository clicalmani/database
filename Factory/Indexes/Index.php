<?php
namespace Clicalmani\Database\Factory\Indexes;

class Index extends IndexType
{
    private $keys = [],
            $cols = [],
            $constraint,
            $reference,
            $onDelete,
            $onUpdate,
            $match;

    function __construct(private $name = '')
    {}

    function key(...$keys)
    {
        if ($this->references) $this->cols = array_merge($this->cols, $keys);
        else $this->keys = array_merge($this->keys, $keys);
        return $this;
    }

    function constraint($symbol)
    {
        $this->constraint = $symbol;
        return $this;
    }

    function references($table)
    {
        $this->constraint = $table;
        return $this;
    }

    function onDeleteCascade()
    {
        $this->onDelete = 'ON DELETE CASCADE';
    }

    function onUpdateCascade()
    {
        $this->onUpdate = 'ON UPDATE CASCADE';
    }

    function onDeleteRestrict()
    {
        $this->onDelete = 'ON DELETE RESTRICT';
    }

    function onUpdateRestrict()
    {
        $this->onUpdate = 'ON UPDATE RESTRICT';
    }

    function onDeleteSetNull()
    {
        $this->onDelete = 'ON DELETE SET NULL';
    }

    function onUpdateSetNull()
    {
        $this->onUpdate = 'ON UPDATE SET NULL';
    }

    function onDeleteNoAction()
    {
        $this->onDelete = 'ON DELETE NO ACTION';
    }

    function onUpdateNoAction()
    {
        $this->onUpdate = 'ON UPDATE NO ACTION';
    }

    function matchFull()
    {
        $this->match = 'MATCH FULL';
    }

    function matchPartial()
    {
        $this->match = 'MATCH PARTIAL';
    }

    function matchSimple()
    {
        $this->match = 'MATCH SIMPLE';
    }

    function render()
    {
        $key = $this->getData() . ' ' . $this->name;

        if ($this->constraint) $key = 'CONSTRAINT ' . $this->constraint . ' ' . $key;

        $key .= '(';

        foreach ($this->keys as $index => $k) {
            if ($index < count($this->keys) - 1) $key .= "`$key`, ";
            else $key .= "`$k`";
        }

        $key .= ') ';

        if ($this->references) {
            $key .= 'REFERENCES ' . $this->references . ' ';

            $key .= '(';

            foreach ($this->cols as $index => $col_name) {
                if ($index < count($this->cols) - 1) $key .= "`$col_name`, ";
                else $key .= "`$col_name`";
            }

            $key .= ') ';

            if ($this->onDelete) $key .= $this->onDelete . ' ';
            if ($this->onUpdate) $key .= $this->onUpdate;
            if ($this->match) $key .= $this->match;
        }

        return $key;
    }
}
