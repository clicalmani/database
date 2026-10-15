<?php
namespace Clicalmani\Database\Builders;

use Clicalmani\Core\Support\Facades\DB;
use Clicalmani\Database\Concerns\QuotesIdentifiers;
use Clicalmani\Database\Contracts;
use Clicalmani\Database\DBQueryBuilder;
use Clicalmani\Database\DBQueryIterator;
use Clicalmani\Database\Factory\DataTypes\DataType;
use Clicalmani\Database\QueryType;

/**
 * Base commune aux instructions de mise à jour (UPDATE).
 *
 * Toute la logique de construction, de rewriting du WHERE et de binding
 * est centralisée ici. Les sous-classes n'ont qu'à redéfinir keyword()
 * et éventuellement les modificateurs supportés.
 */
abstract class AbstractUpdateStatement extends DBQueryBuilder implements \IteratorAggregate
{
    use QuotesIdentifiers;

    /** @var array<string, mixed> */
    protected array $whereBindings = [];

    /** @var array<int|string, array{value: mixed, type: int}> */
    protected array $setBindings = [];

    /**
     * Mot-clé SQL : 'UPDATE'.
     */
    abstract protected function keyword(): string;

    /**
     * Supporte-t-on LOW_PRIORITY ?
     */
    protected function supportsLowPriority(): bool
    {
        return true;
    }

    /**
     * Supporte-t-on IGNORE ?
     */
    protected function supportsIgnore(): bool
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

        $this->options = $this->whereBindings;

        foreach ($this->params['values'] as $i => $type) {
            $value = is_subclass_of($type, \Clicalmani\Database\Factory\DataTypes\DataType::class) 
                ? $type->getValue() 
                : $type;
            $this->options[':' . $this->params['fields'][$i]] = $value;
        }
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

        if (empty($this->params['fields']) || !is_array($this->params['fields'])) {
            throw new \InvalidArgumentException(
                static::class . ": 'fields' doit être un tableau non vide."
            );
        }

        if (!isset($this->params['values']) || !is_array($this->params['values'])) {
            throw new \InvalidArgumentException(
                static::class . ": 'values' doit être un tableau."
            );
        }

        if (count($this->params['fields']) !== count($this->params['values'])) {
            throw new \InvalidArgumentException(sprintf(
                "%s: %d champs pour %d valeurs — les deux doivent correspondre.",
                static::class,
                count($this->params['fields']),
                count($this->params['values'])
            ));
        }
    }

    // ---------------------------------------------------------------------
    //  Construction SQL
    // ---------------------------------------------------------------------

    protected function buildSql(): string
    {
        $sql = $this->keyword() . ' ';

        if ($this->supportsLowPriority() && !empty($this->params['low_priority'])) {
            $sql .= 'LOW_PRIORITY ';
        }

        if ($this->supportsIgnore() && !empty($this->params['ignore'])) {
            $sql .= 'IGNORE ';
        }

        $sql .= $this->buildTablesClause();
        $sql .= ' SET ' . $this->buildSetClause();
        $sql .= $this->buildWhereClause();

        return $sql;
    }

    protected function buildTablesClause(): string
    {
        $tables = array_map(
            fn($table) => $this->sanitizeTable($table),
            $this->params['tables']
        );

        $sql = implode(',', $tables);

        if (!empty($this->params['join'])) {
            $joined = array_map(
                fn($joint) => $this->quoteIdentifier(DB::getPrefix() . $joint['table']),
                $this->params['join']
            );

            if ($joined) {
                $sql .= ', ' . implode(',', $joined);
            }
        }

        return $sql;
    }

    protected function sanitizeTable(string $table): string
    {
        $parts = preg_split('/\s+/', trim($table), -1, PREG_SPLIT_NO_EMPTY);
        $name  = array_shift($parts);
        $alias = $parts ? ' ' . implode(' ', $parts) : '';

        return $this->quoteIdentifier(DB::getPrefix() . $name) . $alias;
    }

    protected function buildSetClause(): string
    {
        $fields = $this->params['fields'];
        $values = $this->params['values'];
        $marker = $this->params['marker'] ?? ':';

        $assignments = [];

        foreach ($fields as $i => $field) {
            $type  = $values[$i];
            $value = $this->unwrapValue($type);

            if ($this->isRawExpression($value)) {
                $assignments[] = $this->quoteIdentifier($field) . " = {$value}";
                continue;
            }

            $placeholder = $marker === '?' ? '?' : ':' . $field;

            $assignments[] = $this->quoteIdentifier($field) . " = {$placeholder}";

            $key = $marker === '?' ? $i + 1 : $field;

            $this->setBindings[$key] = [
                'value' => $value,
                'type'  => $this->getDataType($type),
            ];
        }

        return implode(',', $assignments);
    }

    protected function buildWhereClause(): string
    {
        if (empty($this->params['where'])) {
            return '';
        }

        return ' WHERE ' . $this->rewriteWhereClause($this->params['where']);
    }

    /**
     * Réécrit les placeholders du WHERE en alias uniques (__where_N)
     * pour éviter toute collision avec les placeholders du SET.
     */
    protected function rewriteWhereClause(string $where): string
    {
        $counter = 0;
        $options = $this->options;

        return preg_replace_callback(
            '/\?|:([a-zA-Z_][a-zA-Z0-9_]*)/',
            function ($m) use (&$counter, &$options) {
                $alias = '__where_' . $counter++;

                $value = $m[0] === '?'
                    ? array_shift($options)
                    : ($options[$m[1]] ?? null);

                $this->whereBindings[$alias] = $value;

                return ':' . $alias;
            },
            $where
        );
    }

    protected function unwrapValue(mixed $type): mixed
    {
        return is_subclass_of($type, DataType::class)
            ? $type->getValue()
            : $type;
    }

    // ---------------------------------------------------------------------
    //  Binding
    // ---------------------------------------------------------------------

    protected function bindStatement(Contracts\PreparedStatementInterface $stmt): void
    {
        // 1. SET
        foreach ($this->setBindings as $key => $binding) {
            $stmt->bind($key, $binding['value'], $binding['type']);
        }

        // 2. WHERE (avec alias uniques)
        foreach ($this->whereBindings as $alias => $value) {
            $stmt->bind($alias, $value, $this->getDataType($value));
        }
    }

    // ---------------------------------------------------------------------
    //  API publique
    // ---------------------------------------------------------------------

    /**
     * Retourne le nombre de lignes affectées par l'UPDATE.
     *
     * ⚠️ En MySQL, PDO::rowCount() retourne le nombre de lignes
     * réellement modifiées (pas seulement matchées par le WHERE).
     * Activez PDO::MYSQL_ATTR_FOUND_ROWS pour changer ce comportement.
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