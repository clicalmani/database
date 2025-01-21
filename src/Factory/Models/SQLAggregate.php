<?php
namespace Clicalmani\Database\Factory\Models;

trait SQLAggregate
{
    /**
     * Count the number of rows in the query result
     * 
     * @param string $field [optional] Field to count. Default is '*'
     * @return int
     */
    public function count(string $field = '*') : int
    {
        return $this->query->count($field);
    }

    /**
     * Get the average of a field in the query result
     * 
     * @param string $field Field to average
     * @return float
     */
    public function avg(string $field) : float
    {
        return $this->query->avg($field);
    }

    /**
     * Get the sum of a field in the query result
     * 
     * @param string $field Field to sum
     * @return float
     */
    public function sum(string $field) : float
    {
        return $this->query->sum($field);
    }

    /**
     * Get the minimum value of a field in the query result
     * 
     * @param string $field Field to min
     * @return float
     */
    public function min(string $field) : float
    {
        return $this->query->min($field);
    }

    /**
     * Get the maximum value of a field in the query result
     * 
     * @param string $field Field to max
     * @return float
     */
    public function max(string $field) : float
    {
        return $this->query->max($field);
    }
}