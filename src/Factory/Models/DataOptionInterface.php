<?php
namespace Clicalmani\Database\Factory\Models;

interface DataOptionInterface
{
    /**
     * Returns distinct rows in the select result.
     * 
     * @param bool $distinct True to enable or false to disable
     * @return static
     */
    public function distinct(bool $distinct = true) : static;

    /**
     * Ignores duplicates keys
     * 
     * @param bool $ignore
     * @return static
     */
    public function ignore(bool $ignore = true) : static;

    /**
     * Enable or disable SQL CALC_FOUND_ROWS option
     * 
     * @param bool $calc
     * @return static
     */
    public function calcFoundRows(bool $calc = true) : static;
}
