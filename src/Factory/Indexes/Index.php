<?php
namespace Clicalmani\Database\Factory\Indexes;

/**
 * Class Index
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Index extends IndexType
{
    private $name = '',     // Index name
            $keys = [],     // Key indexes
            $cols = [],     // INdex columns
            $constraint,    // Index constraint
            $references,    // Index references
            $onDelete,      
            $onUpdate,
            $match,
            $prefix;        // Table prefix

    public function __construct(string $name = '')
    {
        $this->name = $name;
        $this->prefix = env('DB_TABLE_PREFIX', '');
    }

    /**
     * Index key
     * 
     * @param mixed ...$keys Keys spread
     * @return static
     */
    public function key(...$keys) : static
    {
        if ($this->references) $this->cols = array_merge($this->cols, $keys);
        else $this->keys = array_merge($this->keys, $keys);
        return $this;
    }

    /**
     * Key constraint
     * 
     * @param string $symbol Constraint symbol
     * @return static
     */
    public function constraint(string $symbol) : static
    {
        $this->constraint = "`$symbol`";
        return $this;
    }

    /**
     * Reference table
     * 
     * @param string $table Table name
     * @param string $key
     * @return static
     */
    public function references(string $table, string $key) : static
    {
        $this->references = "`$this->prefix{$table}` (`$key`)";
        return $this;
    }

    /**
     * Foreign key option
     * 
     * @return static
     */
    public function onDeleteCascade() : static
    {
        $this->onDelete = ' ON DELETE CASCADE';
        return $this;
    }

    /**
     * Foreign key option
     * 
     * @return static
     */
    public function onUpdateCascade()
    {
        $this->onUpdate = ' ON UPDATE CASCADE';
        return $this;
    }

    /**
     * Foreign key option
     * 
     * @return static
     */
    public function onDeleteRestrict() : static
    {
        $this->onDelete = ' ON DELETE RESTRICT';
        return $this;
    }

    /**
     * Foreign key option
     * 
     * @return static
     */
    public function onUpdateRestrict() : static
    {
        $this->onUpdate = ' ON UPDATE RESTRICT';
        return $this;
    }

    /**
     * Foreign key option
     * 
     * @return static
     */
    public function onDeleteSetNull() : static
    {
        $this->onDelete = ' ON DELETE SET NULL';
        return $this;
    }

    /**
     * Foreign key option
     * 
     * @return static
     */
    public function onUpdateSetNull() : static
    {
        $this->onUpdate = ' ON UPDATE SET NULL';
        return $this;
    }

    /**
     * Foreign key option
     * 
     * @return static
     */
    public function onDeleteNoAction() : static
    {
        $this->onDelete = ' ON DELETE NO ACTION';
        return $this;
    }

    /**
     * Foreign key option
     * 
     * @return static
     */
    public function onUpdateNoAction() : static
    {
        $this->onUpdate = ' ON UPDATE NO ACTION';
        return $this;
    }

    /**
     * Match full index
     * 
     * @return static
     */
    public function matchFull() : static
    {
        $this->match = ' MATCH FULL';
        return $this;
    }

    /**
     * Match partial index
     * 
     * @return static
     */
    public function matchPartial() : static
    {
        $this->match = ' MATCH PARTIAL';
        return $this;
    }

    /**
     * Match simple index
     * 
     * @return static
     */
    public function matchSimple() : static
    {
        $this->match = ' MATCH SIMPLE';
        return $this;
    }

    /**
     * Render function
     * 
     * @return string
     */
    public function render() : string
    {
        $key = $this->getData() . ' ' . $this->name;

        if ($this->constraint) $key = 'CONSTRAINT ' . $this->constraint . ' ' . $key;

        if ( $this->keys ) {
            $key .= ' (';

            foreach ($this->keys as $index => $k) {
                $k = trim($k);
                if ($index < count($this->keys) - 1) $key .= "`$k`, ";
                else $key .= "`$k`";
            }

            $key .= ')';
        }

        if ($this->references) {
            $key .= ' REFERENCES ' . $this->references . ' ';
            
            if ($this->onDelete) $key .= $this->onDelete . ' ';
            if ($this->onUpdate) $key .= $this->onUpdate . ' ';
            if ($this->match) $key .= $this->match . ' ';
        }

        return $key;
    }
}
