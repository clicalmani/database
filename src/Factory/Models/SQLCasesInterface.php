<?php
namespace Clicalmani\Database\Factory\Models;

interface SQLCasesInterface
{
    /**
     * Add the SQL IGNORE operator.
     * 
     * @param bool $ignore
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function ignore(bool $ignore = true) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * Enable or disable SQL DISTINCT
     * 
     * @param bool $distinct
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function distinct(bool $distinct = true) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * Enable or disable SQL_CALC_FOUND_ROWS
     * 
     * @param bool $calc
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function calcFoundRows(bool $calc = true) : \Clicalmani\Database\Factory\Models\ModelInterface;
}