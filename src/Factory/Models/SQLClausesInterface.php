<?php
namespace Clicalmani\Database\Factory\Models;

interface SQLClausesInterface
{
    /**
     * SQL where clause
     * 
     * @param ?string $criteria
     * @param ?array $options Criteria options
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public static function where(?string $criteria = '1', ?array $options = []) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * Like where clause but with and as operator.
     * 
     * @param ?string $criteria
     * @param ?array $options Criteria options
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function whereAnd(?string $criteria = '1', ?array $options = []) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * Like where clause but with or as operator.
     * 
     * @param ?string $criteria
     * @param ?array $options Criteria options
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function whereOr(string $criteria = '1', ?array $options = []) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * SQL order by clause.
     * 
     * @param string $order
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function orderBy(string $order) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * SQL having clause.
     * 
     * @param string $criteria
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function having(string $criteria) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * Add the SQL GROUP BY operator the query.
     * 
     * @param string $criteria
     * @param ?bool $with_rollup
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function groupBy(string $criteria, ?bool $with_rollup = false) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * Add the SQL FROM clause to DELETE query.
     * 
     * @param string $fields
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function from(string $fields) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * Add the SQL LIMIT clause to the query.
     * 
     * @param ?int $offset
     * @param ?int $row_count
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function limit(?int $offset = 0, ?int $row_count = 1) : \Clicalmani\Database\Factory\Models\ModelInterface;
}