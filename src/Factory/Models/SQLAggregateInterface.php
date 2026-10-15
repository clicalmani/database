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
     * @return mixed
     */
    public function avg(string $field, mixed $default = null) : mixed;

    /**
     * Get the sum of a field in the query result
     * 
     * @param string $field Field to sum
     * @return mixed
     */
    public function sum(string $field, mixed $default = null) : mixed;

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