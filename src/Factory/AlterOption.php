<?php
namespace Clicalmani\Database\Factory;

use Clicalmani\Database\Factory\DataTypes\DataType;

/**
 * Class AlterOption
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class AlterOption extends DataType
{
    /**
     * Add column
     * 
     * @param string $name Column name
     * @return static
     */
    public function addColumn(string $name) : static
    {
        $this->data = "ADD COLUMN `$name`";
        return $this;
    }

    /**
     * Drop column
     * 
     * @param string $name Column name
     * @return static
     */
    public function dropColumn(string $name) : static
    {
        $this->data = 'DROP COLUMN ' . $name;
        return $this;
    }

    /**
     * Modify column
     * 
     * @param string $name Column name
     * @return static
     */
    public function modifyColumn(string $name) : static
    {
        $this->data = 'MODIFY ' . $name;
        return $this;
    }

    /**
     * Alter column
     * 
     * @param string $name Column name
     * @return static
     */
    public function alterColumn(string $name) : static
    {
        $this->data = 'ALTER COLUMN ' . $name;
        return $this;
    }

    /**
     * Set column default value
     * 
     * @param string $value
     * @return static
     */
    public function setDefault(string $value) : static
    {
        $this->data .= ' SET DEFAULT ' . $value;
        return $this;
    }

    /**
     * Drop column default value
     * 
     * @return static
     */
    public function dropDefault() : static
    {
        $this->data .= ' DROP DEFAULT';
        return $this;
    }

    /**
     * Change column
     * 
     * @param string $name Column name
     * @return static
     */
    public function changeColumn(string $name) : static
    {
        $this->data = " CHANGE COLUMN `$name` `$name`";
        return $this;
    }

    /**
     * Modification occurs at the upper top
     * 
     * @return static
     */
    public function first() : static
    {
        $this->data .= ' FIRST';
        return $this;
    }

    /**
     * Modification occurs after column
     * 
     * @param string $column Column name
     * @return static
     */
    public function after(string $column) : static
    {
        $this->data .= ' AFTER ' . $column;
        return $this;
    }

    /**
     * Drop primary key
     * 
     * @return static
     */
    public function dropPrimaryKey() : static
    {
        $this->data = 'DROP PRIMARY KEY';
        return $this;
    }

    /**
     * Add primary key
     * 
     * @param string $key Primary key
     * @return static
     */
    public function addPrimaryKey(string $key) : static
    {
        $this->data = "ADD PRIMARY KEY (`$key`)";
        return $this;
    }

    /**
     * Add index
     * 
     * @param string $name Index name
     * @param array $columns Index columns
     * @return static
     */
    public function addIndex(string $name, ?array $columns = []) : static
    {
        $this->data = "ADD INDEX $name (" . join(',', $columns) . ")";
        return $this;
    }

    /**
     * Add unique index
     * 
     * @param string $name Index name
     * @param ?array $columns Index columns
     * @return static
     */
    public function addUniqueIndex(string $name, ?array $columns = []) : static
    {
        $this->data = "ADD UNIQUE INDEX $name (" . join(',', $columns) . ")";
        return $this;
    }

    /**
     * Drop index
     * 
     * @param string $name Index name
     * @return static
     */
    public function dropIndex(string $name) : static
    {
        $this->data = "DROP INDEX $name";
        return $this;
    }

    /**
     * Add constraint
     * 
     * @param string $constraint
     * @return static
     */
    public function addConstraint($constraint) : static
    {
        $this->data = "ADD CONSTRAINT $constraint";
        return $this;
    }

    /**
     * Add index foreign key
     * 
     * @param ?array $columns Columns
     * @return static
     */
    public function foreignKey(?array $columns = []) : static
    {
        $this->data .= ' FOREIGN KEY (' . join(',', $columns) . ')';
        return $this;
    }

    /**
     * Add table references
     * 
     * @param string $table
     * @param ?array $columns
     * @return static
     */
    public function references(string $table, ?array $columns = []) : static
    {
        $this->data .= ' REFERENCES ' . env('DB_TABLE_PREFIX', '') . $table . ' (' . join(',', $columns) . ')';
        return $this;
    }

    /**
     * Alias of references()
     * 
     * @deprecated
     * @param string $table
     * @param ?array $columns
     * @return static
     */
    public function referencies(string $table, ?array $columns = []) : static
    {
        return $this->references($table, $columns);
    }

    /**
     * On delete action
     * 
     * @return static
     */
    public function onDeleteCascade() : static
    {
        $this->data .= ' ON DELETE CASCADE';
        return $this;
    }

    /**
     * On update action
     * 
     * @return static
     */
    public function onUpdateCascade() : static
    {
        $this->data .= ' ON UPDATE CASCADE';
        return $this;
    }

    /**
     * On delete action (restrict)
     * 
     * @return static
     */
    public function onDeleteRestrict() : static
    {
        $this->data .= ' ON DELETE RESTRICT';
        return $this;
    }

    /**
     * On update action (restrict)
     * 
     * @return static
     */
    public function onUpdateRestrict() : static
    {
        $this->data .= ' ON UPDATE RESTRICT';
        return $this;
    }

    /**
     * On delete action (set null)
     * 
     * @return static
     */
    public function onDeleteSetNull() : static
    {
        $this->data .= ' ON DELETE SET NULL';
        return $this;
    }

    /**
     * On update action (set null)
     * 
     * @return static
     */
    public function onUpdateSetNull() : static
    {
        $this->data .= ' ON UPDATE SET NULL';
        return $this;
    }

    /**
     * On delete action (no action)
     * 
     * @return static
     */
    public function onDeleteNoAction() : static
    {
        $this->data .= ' ON DELETE NO ACTION';
        return $this;
    }

    function onUpdateNoAction()
    {
        $this->data .= ' ON UPDATE NO ACTION';
        return $this;
    }

    /**
     * Set unique index
     * 
     * @param string $name Index name
     * @param ?array $columns Index columns
     * @return static
     */
    public function uniqueIndex(string $name, ?array $columns = []) : static
    {
        $this->data .= " UNIQUE $name (" . join(',', $columns) . ")";
        return $this;
    }

    /**
     * Drop foreign key
     * 
     * @param string $constraint
     * @return static
     */
    public function dropForeignKey(string $constraint) : static
    {
        $this->data = "DROP FOREIGN KEY $constraint";
        return $this;
    }

    /**
     * Rename a table
     * 
     * @param string $new_name
     * @return static
     */
    public function renameTo($new_name) : static
    {
        $prefix = env('DB_TABLE_PREFIX', '');
        $this->data = "RENAME TO $prefix{$new_name}";
        return $this;
    }

    /**
     * Render changes
     * 
     * @return string
     */
    public function render() : string
    {
        return $this->data;
    }
}
