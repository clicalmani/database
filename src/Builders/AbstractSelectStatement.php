<?php
namespace Clicalmani\Database\Builders;

use Clicalmani\Core\Support\Facades\DB;
use Clicalmani\Database\Concerns\QuotesIdentifiers;
use Clicalmani\Database\Contracts;
use Clicalmani\Database\DBQueryBuilder;
use Clicalmani\Database\DBQueryIterator;
use Clicalmani\Database\QueryType;

/**
 * Base commune aux instructions de sélection (SELECT).
 *
 * Toute la logique de construction SQL est centralisée ici.
 * Les sous-classes n'ont qu'à redéfinir keyword() si nécessaire.
 */
abstract class AbstractSelectStatement extends DBQueryBuilder implements \IteratorAggregate
{
    use QuotesIdentifiers;

    /**
     * Mot-clé SQL : 'SELECT'.
     */
    abstract protected function keyword(): string;

    /**
     * Supporte-t-on SQL_CALC_FOUND_ROWS ?
     */
    protected function supportsCalcFoundRows(): bool
    {
        return true;
    }

    /**
     * Supporte-t-on DISTINCT ?
     */
    protected function supportsDistinct(): bool
    {
        return true;
    }

    public function __construct(
        protected $params,
        protected $options,
        Contracts\QueryExecutorInterface $executor,
        QueryType $type,
    ) {
        parent::__construct($params, $options, $executor, $type);

        $this->validateParams();
        $this->sql = $this->buildSql();
    }

    // ---------------------------------------------------------------------
    //  Validation
    // ---------------------------------------------------------------------

    protected function validateParams(): void
    {
        if (empty($this->params['tables']) || !is_array($this->params['tables'])) {
            throw new \InvalidArgumentException(
                static::class . ": 'tables' doit être un tableau non vide."
            );
        }

        if (isset($this->params['limit']) && !is_numeric($this->params['limit'])) {
            throw new \InvalidArgumentException(
                static::class . ": 'limit' doit être numérique."
            );
        }

        if (isset($this->params['offset']) && !is_numeric($this->params['offset'])) {
            throw new \InvalidArgumentException(
                static::class . ": 'offset' doit être numérique."
            );
        }
    }

    // ---------------------------------------------------------------------
    //  Construction SQL
    // ---------------------------------------------------------------------

    protected function buildSql(): string
    {
        return $this->buildSelectClause()
            . $this->buildFromClause()
            . $this->buildJoinClause()
            . $this->buildWhereClause()
            . $this->buildGroupByClause()
            . $this->buildHavingClause()
            . $this->buildOrderByClause()
            . $this->buildLimitClause();
    }

    protected function buildSelectClause(): string
    {
        $sql = $this->keyword() . ' ';

        if ($this->supportsDistinct() && isset($this->params['distinct']) && $this->params['distinct']) {
            $sql .= 'DISTINCT ';
        }

        if ($this->supportsCalcFoundRows() && !empty($this->params['calc'])) {
            $sql .= 'SQL_CALC_FOUND_ROWS ';
        }

        $sql .= $this->params['fields'] ?? '*';

        return $sql;
    }

    protected function buildFromClause(): string
    {
        return ' FROM ' . implode(',', $this->sanitizeTables($this->params['tables']));
    }

    protected function buildJoinClause(): string
    {
        if (!isset($this->params['join'])) {
            return '';
        }

        $sql = '';

        foreach ($this->params['join'] as $joint) {
            $sql .= ' ' . $this->addJoin($joint);
        }

        return $sql;
    }

    protected function buildWhereClause(): string
    {
        $conditions = [];

        // WHERE explicite
        if (isset($this->params['where'])) {
            $conditions[] = $this->params['where'];
        }

        if (empty($conditions)) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', $conditions);
    }

    protected function buildGroupByClause(): string
    {
        if (!isset($this->params['group_by'])) {
            return '';
        }

        return ' GROUP BY ' . $this->params['group_by'];
    }

    protected function buildHavingClause(): string
    {
        if (!isset($this->params['having'])) {
            return '';
        }

        return ' HAVING ' . $this->params['having'];
    }

    protected function buildOrderByClause(): string
    {
        if (!isset($this->params['order_by'])) {
            return '';
        }

        return ' ORDER BY ' . $this->params['order_by'];
    }

    protected function buildLimitClause(): string
    {
        if (!isset($this->params['limit'])) {
            return '';
        }

        $limit  = (int) $this->params['limit'];

        if (!isset($this->params['offset'])) {
            return " LIMIT {$limit}";
        }
        
        $offset = (int) ($this->params['offset'] ?? 0);

        return " LIMIT {$offset}, {$limit}";
    }

    // ---------------------------------------------------------------------
    //  Hydratation du résultat
    // ---------------------------------------------------------------------

    protected function hydrateResult(): void
    {
        if (!$this->statement || !$this->type->isFetchable()) {
            return;
        }

        if ($this->result === null) {
            return;
        }

        while ($row = $this->executor->fetch($this->statement, \PDO::FETCH_OBJ)) {
            $this->result->add($row);
        }
    }

    // ---------------------------------------------------------------------
    //  Utilitaires
    // ---------------------------------------------------------------------

    protected function addJoin(array $join): string
    {
        $ret = '';

        if (isset($join['type'])) {
            $ret .= ' ' . static::JOIN_TYPES[strtolower($join['type'])];
        }

        if (isset($join['table'])) {
            $ret .= ' ' . implode(',', $this->sanitizeTables([$join['table']]));
        }

        if (isset($join['sub_query'])) {
            $ret .= ' (' . $join['sub_query'] . ')';

            if (isset($join['alias'])) {
                $ret .= ' ' . $join['alias'];
            }
        }

        if (isset($join['criteria'])) {
            $ret .= ' ' . $join['criteria'];
        }

        if (isset($join['condition'])) {
            $ret .= ' ' . $join['condition'];
        }

        return $ret;
    }

    // ---------------------------------------------------------------------
    //  API publique
    // ---------------------------------------------------------------------

    public function getIterator(): \Traversable
    {
        return new DBQueryIterator($this);
    }
}