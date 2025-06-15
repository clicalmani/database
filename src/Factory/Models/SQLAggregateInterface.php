<?php
namespace Clicalmani\Database\Factory\Models;

interface SQLAggregateInterface
{
    /**
     * Count the number of rows in the query result
     * 
     * @param string $field [optional] Field to count. Default is '*'
     * @return int
     */
    public function count(string $field = '*') : int;

    /**
     * Get the average of a field in the query result
     * 
     * @param string $field Field to average
     * @return float
     */
    public function avg(string $field) : float;

    /**
     * Get the sum of a field in the query result
     * 
     * @param string $field Field to sum
     * @return float
     */
    public function sum(string $field) : float;

    /**
     * Get the minimum value of a field in the query result
     * 
     * @param string $field Field to min
     * @return float
     */
    public function min(string $field) : float;

    /**
     * Get the maximum value of a field in the query result
     * 
     * @param string $field Field to max
     * @return float
     */
    public function max(string $field) : float;
}