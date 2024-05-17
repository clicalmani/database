<?php
namespace Clicalmani\Database\Factory\Models;

interface DataClauseInterface
{
    /**
     * Define the where clause of the SQL statement
     * 
     * @param ?string $criteria Condition
     * @param ?array $options Parameters options
     * @return static
     */
    public static function where(?string $criteria = '1', ?array $options = []) : static;

    /**
     * Alias of where. Useful when using where multiple times with AND as the conditional operator.
     * 
     * @param ?string $criteria
     * @param ?array $options
     * @return static
     */
    public function whereAnd(?string $criteria = '1', ?array $options = []) : static;

    /**
     * Same as whereAnd with the difference of operator which is in this case OR.
     * 
     * @param ?string $criteria 
     * @param ?array $options
     * @return static
     */
    public function whereOr(string $criteria = '1', ?array $options = []) : static;

    /**
     * Define the SQL order by clause.
     * 
     * @param string $order SQL order by clause
     * @return static
     */
    public function orderBy(string $order) : static;

    /**
     * Define the from clause when deleting from joined models.
     * 
     * @param string $fields SQL FROM clause
     * @return static
     */
    public function from(string $fields) : static;

    /**
     * Defines SQL having clause.
     * 
     * @param string $criteria Having clause
     * @return static
     */
    public function having(string $criteria) : static;

    /**
     * Defines SQL group by clause.
     * 
     * @param string $criteria SQL group by criteria
     * @param ?bool $with_rollup 
     * @return static
     */
    public function groupBy(string $criteria, ?bool $with_rollup = false) : static;

    /**
     * Limit the number of rows to be returned in the query result.
     * 
     * @param ?int $offset Offset
     * @param ?int $row_count Row count
     * @return static
     */
    public function limit(?int $offset = 0, ?int $row_count = 1) : static;
}
