<?php
namespace Clicalmani\Database\Factory\Models;

trait SQLCases 
{
    public function ignore(bool $ignore = true) : static
    {
        $this->insert_ignore = $ignore;
        return $this;
    }

    public function distinct(bool $distinct = true) : static
    {
        $this->distinct = $distinct;
        return $this;
    }

    public function calcFoundRows(bool $calc = true) : static
    {
        $this->calc_found_rows = $calc;
        return $this;
    }
}