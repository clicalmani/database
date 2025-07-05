<?php
namespace Clicalmani\Database\Factory\Models;

interface SoftDeleteInterface
{
    /**
     * Include soft deleted records in the query results.
     * 
     * @return static
     */
    public function withTrashed() : static;

    /**
     * Include only soft deleted records in the query results.
     * 
     * @return static
     */
    public function onlyTrashed() : static;

    /**
     * Restore a soft deleted record.
     * 
     * @return bool
     */
    public function restore() : bool;
}