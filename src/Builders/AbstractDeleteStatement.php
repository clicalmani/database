<?php
namespace Clicalmani\Database\Builders;

use Clicalmani\Core\Support\Facades\DB;
use Clicalmani\Database\Concerns\QuotesIdentifiers;
use Clicalmani\Database\Contracts;
use Clicalmani\Database\DBQueryBuilder;
use Clicalmani\Database\DBQueryIterator;
use Clicalmani\Database\QueryType;

/**
 * Base commune aux instructions de suppression (DELETE).
 *
 * Supporte :
 *  - DELETE single-table : DELETE FROM table WHERE ...
 *  - DELETE multi-table (MySQL) : DELETE t1, t2 FROM t1 USING t2 WHERE ...
 *  - ORDER BY et LIMIT (single-table uniquement, MySQL)
 *  - PARTITION (single-table)
 *
 * ⚠️ DELETE multi-table et DELETE avec ORDER BY/LIMIT sont des extensions
 * MySQL/MariaDB. Sur d'autres SGBD, seul le DELETE single-table est supporté.
 */
abstract class AbstractDeleteStatement extends DBQueryBuilder implements \IteratorAggregate
{
    use QuotesIdentifiers;

    /**
     * Mot-clé SQL : 'DELETE'.
     */
    abstract protected function keyword(): string;

    /**
     * Supporte-t-on ORDER BY / LIMIT ?
     */
    protected function supportsOrderByLimit(): bool
    {
        return true;
    }

    /**
     * Supporte-t-on la syntaxe multi-table (USING) ?
     */
    protected function supportsMultiTable(): bool
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

        if (
            $this->isMultiTable()
            && $this->supportsOrderByLimit()
            && (isset($this->params['order_by']) || isset($this->params['limit']))
        ) {
            throw new \InvalidArgumentException(
                static::class . ": ORDER BY et LIMIT ne sont pas supportés en DELETE multi-table."
            );
        }
    }

    protected function isMultiTable(): bool
    {
        return count($this->params['tables']) > 1
            || !empty($this->params['join']);
    }

    // ---------------------------------------------------------------------
    //  Construction SQL
    // ---------------------------------------------------------------------

    protected function buildSql(): string
    {
        $sql = $this->keyword() . ' ';

        // Signatures (DELETE t1, t2 FROM ...)
        if (!empty($this->params['signatures']) && is_array($this->params['signatures'])) {
            $sql .= implode(',', array_map(
                fn($s) => $this->quoteIdentifier($s),
                $this->params['signatures']
            )) . ' ';
        }

        if ($this->isMultiTable() && $this->supportsMultiTable()) {
            $sql .= $this->buildMultiTableFrom();
        } else {
            $sql .= $this->buildSingleTableFrom();
        }

        $sql .= $this->buildWhereClause();

        if (!$this->isMultiTable() && $this->supportsOrderByLimit()) {
            $sql .= $this->buildOrderByClause();
            $sql .= $this->buildLimitClause();
        }

        return $sql;
    }

    protected function buildMultiTableFrom(): string
    {
        $prefix = DB::getPrefix();
        $tables = array_map(
            fn($t) => $this->quoteIdentifier($prefix . $t),
            $this->params['tables']
        );

        $sql = '';

        if (isset($this->params['fields'])) {
            $sql .= 'FROM ' . $this->params['fields'] . ' ';
        }

        $sql .= 'USING ' . implode(',', $tables) . ' ';

        if (isset($this->params['join']) && is_array($this->params['join'])) {
            foreach ($this->params['join'] as $joint) {
                $sql .= $this->addJoin($joint);
            }
        }

        return rtrim($sql);
    }

    protected function buildSingleTableFrom(): string
    {
        // Retire l'alias de table (MySQL DELETE ne l'accepte pas)
        $table = $this->params['tables'][0];
        $parts = preg_split('/\s+/', trim($table), -1, PREG_SPLIT_NO_EMPTY);
        $name  = array_shift($parts);

        $quotedTable = $this->quoteIdentifier(DB::getPrefix() . $name);

        $sql = 'FROM ' . $quotedTable . ' ';

        if (isset($this->params['partitions']) && is_array($this->params['partitions'])) {
            $partitions = implode(',', array_map(
                fn($p) => $this->quoteIdentifier($p),
                $this->params['partitions']
            ));
            $sql .= 'PARTITION (' . $partitions . ') ';
        }

        return rtrim($sql);
    }

    protected function buildWhereClause(): string
    {
        if (empty($this->params['where'])) {
            return '';
        }

        $where = $this->params['where'];

        // Single-table : retire les préfixes d'alias (users.id → id)
        if (!$this->isMultiTable()) {
            $where = preg_replace('/([a-zA-Z0-9-_]+)\./', '', $where);
        }

        return ' WHERE ' . $where;
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

        return ' LIMIT ' . (int) $this->params['limit'];
    }

    protected function addJoin(array $join): string
    {
        $sql = '';

        if (isset($join['type'])) {
            $sql .= static::JOIN_TYPES[strtolower($join['type'])] . ' ';
        }

        if (isset($join['table'])) {
            $sql .= $this->quoteIdentifier(DB::getPrefix() . $join['table']) . ' ';
        }

        if (isset($join['criteria'])) {
            $sql .= $join['criteria'] . ' ';
        }

        return $sql;
    }

    // ---------------------------------------------------------------------
    //  API publique
    // ---------------------------------------------------------------------

    /**
     * Retourne le nombre de lignes supprimées.
     */
    public function getAffectedRows(): int
    {
        return $this->num_rows;
    }

    public function getIterator(): \Traversable
    {
        return new DBQueryIterator($this);
    }
}