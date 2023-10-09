<?php
namespace Clicalmani\Database\Factory;

use Clicalmani\Database\Factory\DataTypes\DataType;

class AlterOption extends DataType
{
    function addColumn($name)
    {
        $this->data = "ADD COLUMN `$name`";
        return $this;
    }

    function dropColumn($name)
    {
        $this->data = 'DROP COLUMN ' . $name;
        return $this;
    }

    function modifyColumn($name)
    {
        $this->data = 'MODIFY ' . $name;
        return $this;
    }

    function alterColumn($name)
    {
        $this->data = 'ALTER COLUMN ' . $name;
        return $this;
    }

    function setDefault($value)
    {
        $this->data .= ' SET DEFAULT ' . $value;
        return $this;
    }

    function dropDefault()
    {
        $this->data .= ' DROP DEFAULT';
        return $this;
    }

    function changeColumn($name)
    {
        $this->data = " CHANGE COLUMN `$name` `$name`";
        return $this;
    }

    function first()
    {
        $this->data .= ' FIRST';
        return $this;
    }

    function after($column)
    {
        $this->data .= ' AFTER ' . $column;
        return $this;
    }

    function dropPrimaryKey()
    {
        $this->data = 'DROP PRIMARY KEY';
        return $this;
    }

    public function addPrimaryKey(string $key) : static
    {
        $this->data = "ADD PRIMARY KEY (`$key`)";
        return $this;
    }

    function addIndex($name, $columns = [])
    {
        $this->data = "ADD INDEX $name (" . join(',', $columns) . ")";
        return $this;
    }

    function addUniqueIndex($name, $columns = [])
    {
        $this->data = "ADD UNIQUE INDEX $name (" . join(',', $columns) . ")";
        return $this;
    }

    function dropIndex($name)
    {
        $this->data = "DROP INDEX $name";
        return $this;
    }

    function addConstraint($name)
    {
        $this->data = "ADD CONSTRAINT $name";
        return $this;
    }

    function foreignKey($columns = [])
    {
        $this->data .= ' FOREIGN KEY (' . join(',', $columns) . ')';
        return $this;
    }

    function referencies($table, $columns = [])
    {
        $this->data .= ' REFERENCES ' . env('DB_TABLE_PREFIX', '') . $table . ' (' . join(',', $columns) . ')';
        return $this;
    }

    function onDeleteCascade()
    {
        $this->data .= ' ON DELETE CASCADE';
        return $this;
    }

    function onUpdateCascade()
    {
        $this->data .= ' ON UPDATE CASCADE';
        return $this;
    }

    function onDeleteRestrict()
    {
        $this->data .= ' ON DELETE RESTRICT';
        return $this;
    }

    function onUpdateRestrict()
    {
        $this->data .= ' ON UPDATE RESTRICT';
        return $this;
    }

    function onDeleteSetNull()
    {
        $this->data .= ' ON DELETE SET NULL';
        return $this;
    }

    function onUpdateSetNull()
    {
        $this->data .= ' ON UPDATE SET NULL';
        return $this;
    }

    function onDeleteNoAction()
    {
        $this->data .= ' ON DELETE NO ACTION';
        return $this;
    }

    function onUpdateNoAction()
    {
        $this->data .= ' ON UPDATE NO ACTION';
        return $this;
    }

    function uniqueIndex($name, $columns = [])
    {
        $this->data .= " UNIQUE $name (" . join(',', $columns) . ")";
        return $this;
    }

    function dropForeignKey($constraint)
    {
        $this->data = "DROP FOREIGN KEY $constraint";
        return $this;
    }

    function renameTo($new_name)
    {
        $prefix = env('DB_TABLE_PREFIX', '');
        $this->data = "RENAME TO $prefix{$new_name}";
        return $this;
    }

    function render()
    {
        return $this->data;
    }
}
