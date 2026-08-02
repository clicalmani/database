<?php
namespace Clicalmani\Database\SubQueries;

use Clicalmani\Database\QueryInterface;

interface SubQueryInterface
{
    /**
     * Invoke the sub-query by specifying the boolean operator
     * to be used in the WHERE condition.
     * @param mixed ...$args Arguments to boe used
     */
    public function __invoke(mixed ...$args): QueryInterface;
}