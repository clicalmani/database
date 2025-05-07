<?php
namespace Clicalmani\Database\Factory\Models;

trait SQLCases 
{
    /**
     * Add the SQL IGNORE operator.
     * 
     * @param bool $ignore
     * @return static
     */
    public function ignore(bool $ignore = true) : static
    {
        $this->insert_ignore = $ignore;
        return $this;
    }

    /**
     * Enable or disable SQL DISTINCT
     * 
     * @param bool $distinct
     * @return static
     */
    public function distinct(bool $distinct = true) : static
    {
        $this->distinct = $distinct;
        return $this;
    }

    /**
     * Enable or disable SQL_CALC_FOUND_ROWS
     * 
     * @param bool $calc
     * @return static
     */
    public function calcFoundRows(bool $calc = true) : static
    {
        $this->calc_found_rows = $calc;
        return $this;
    }
}