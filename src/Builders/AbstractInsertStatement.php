<?php
namespace Clicalmani\Database\Builders;

use Clicalmani\Core\Support\Facades\DB;
use Clicalmani\Database\Concerns\QuotesIdentifiers;
use Clicalmani\Database\Concerns\ResolvesServerVersion;
use Clicalmani\Database\Contracts;
use Clicalmani\Database\DBQueryBuilder;
use Clicalmani\Database\DBQueryIterator;
use Clicalmani\Database\Factory\DataTypes\DataType;
use Clicalmani\Database\QueryType;

abstract class AbstractInsertStatement extends DBQueryBuilder implements \IteratorAggregate
{
    use QuotesIdentifiers;
    use ResolvesServerVersion;

    abstract protected function keyword(): string;

    protected function supportsIgnore(): bool { return false; }
    protected function supportsOnDuplicateKey(): bool { return false; }

    public function __construct(
        protected $params,
        protected $options,
        Contracts\QueryExecutorInterface $executor,
        QueryType $type,
    ) {
        parent::__construct($params, $options, $executor, $type);

        $this->validateParams();
        $this->sql = $this->buildSql();
        
        foreach ($this->params['values'] as $rowId => $row) {
			foreach ($row as $fieldId => $type) {
				if ( is_subclass_of($type, \Clicalmani\Database\Factory\DataTypes\DataType::class) ) $value = $type->getValue();
				else $value = $type;
                $this->options[$this->params['fields'][$fieldId] . '_' . $rowId] = $value;
			}
		}
    }

    // ---------------------------------------------------------------------
    //  API fluide
    // ---------------------------------------------------------------------

    /**
     * Ajoute la clause ON DUPLICATE KEY UPDATE.
     *
     * @param array $columns
     *   - Liste simple : ['title', 'price']
     *   - Map col => expression : ['visit_count' => 'visit_count + 1']
     */
    public function onDuplicateKeyUpdate(array $columns): static
    {
        if (!$this->supportsOnDuplicateKey()) {
            throw new \LogicException(
                static::class . " ne supporte pas ON DUPLICATE KEY UPDATE."
            );
        }

        $this->params['on_duplicate'] = $columns;
        $this->sql = $this->buildSql();

        return $this;
    }

    /**
     * Active la syntaxe AS alias (MySQL 8.0.19+ uniquement).
     * Ignorée silencieusement sur MySQL 5.7 / MariaDB.
     */
    public function usingAlias(string $alias): static
    {
        $this->params['alias'] = $alias;
        $this->sql = $this->buildSql();

        return $this;
    }

    /**
     * Active IGNORE (INSERT uniquement).
     */
    public function ignore(bool $ignore = true): static
    {
        if (!$this->supportsIgnore()) {
            throw new \LogicException(
                static::class . " ne supporte pas IGNORE."
            );
        }

        $this->params['ignore'] = $ignore;
        $this->sql = $this->buildSql();

        return $this;
    }

    /**
     * Exécute l'INSERT/REPLACE et retourne l'ID auto-incrémenté généré.
     *
     * Équivaut à :
     *   $builder->query();
     *   return $builder->getInsertId();
     *
     * ⚠️ En cas d'INSERT multi-row, retourne le PREMIER ID généré.
     *    Utiliser getInsertedIds() pour la liste complète.
     *
     * ⚠️ Retourne null si :
     *  - La table n'a pas de colonne AUTO_INCREMENT.
     *  - L'INSERT a été ignoré (INSERT IGNORE sur doublon).
     *  - Le driver ne supporte pas lastInsertId().
     *
     * @throws \PDOException En cas d'erreur SQL.
     * @throws \RuntimeException Si l'ID n'a pas pu être récupéré.
     */
    public function getId(): int|string|null
    {
        $this->query();
        
        if (!$this->status) {
            throw new \RuntimeException(sprintf(
                "insertGetId: l'exécution a échoué — [%d] %s",
                $this->error_code,
                $this->error_msg
            ));
        }

        return $this->insert_id;
    }

    /**
     * Exécute l'INSERT et retourne la liste des IDs auto-incrémentés générés.
     *
     * ⚠️ NON GARANTI si :
     *  - INSERT IGNORE a skippé des lignes
     *  - Des triggers insèrent dans d'autres tables
     *  - auto_increment_increment != 1
     *
     * @return array<int|string>
     */
    public function getIds(): array
    {
        $this->query();

        if (!$this->status) {
            throw new \RuntimeException(sprintf(
                "insertGetIds: l'exécution a échoué — [%d] %s",
                $this->error_code,
                $this->error_msg
            ));
        }

        return $this->getInsertedIds();
    }

    /**
     * Comme insertGetId(), mais lève une exception si aucun ID n'est retourné.
     *
     * Utile pour les tables avec AUTO_INCREMENT obligatoire où l'absence
     * d'ID indique une anomalie (INSERT IGNORE skippé, trigger, etc.).
     *
     * @throws \RuntimeException Si aucun ID n'est disponible après l'exécution.
     */
    public function getIdOrFail(): int|string
    {
        $id = $this->getId();
        
        if ($id === null || $id === 0 || $id === '0') {
            throw new \RuntimeException(sprintf(
                "insertGetIdOrFail: aucun ID généré. "
                . "Causes possibles : INSERT IGNORE skippé, table sans AUTO_INCREMENT, "
                . "trigger supprimant l'insertion, ou driver PDO non supporté. "
                . "[%d] %s",
                $this->error_code,
                $this->error_msg
            ));
        }

        return $id;
    }

    /**
     * Exécute l'INSERT et retourne LAST_INSERT_ID() via la fonction MySQL
     * directement dans la requête (au lieu de PDO::lastInsertId()).
     *
     * Plus sûr en environnement distribué car LAST_INSERT_ID() est
     * par-connection et retourné atomiquement.
     *
     * ⚠️ MySQL/MariaDB uniquement.
     */
    public function getIdWithLock(): int
    {
        // Ajoute "RETURNING" si le SGBD le supporte, sinon fallback
        // Pour MySQL : on ne peut pas faire RETURNING. On utilise
        // PDO::lastInsertId() qui EST la valeur de LAST_INSERT_ID() côté serveur.
        // Donc insertGetId() suffit — cette méthode est un alias documentaire.
        return (int) $this->getIdOrFail();
    }

    // ---------------------------------------------------------------------
    //  Validation
    // ---------------------------------------------------------------------

    protected function validateParams(): void
    {
        if (empty($this->params['table'])) {
            throw new \InvalidArgumentException(static::class . ": 'table' est requis.");
        }

        $fields = $this->params['fields'] ?? null;

        if (empty($fields) || !is_array($fields)) {
            throw new \InvalidArgumentException(
                static::class . ": 'fields' doit être un tableau non vide."
            );
        }

        $rows = $this->normalizeValues();

        if (empty($rows)) {
            throw new \InvalidArgumentException(
                static::class . ": 'values' doit contenir au moins une ligne."
            );
        }

        foreach ($rows as $i => $row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException(
                    static::class . ": 'values' hétérogène — la ligne #$i n'est pas un tableau."
                );
            }
            if (count($row) !== count($fields)) {
                throw new \InvalidArgumentException(sprintf(
                    "%s: la ligne #%d a %d valeurs mais %d champs déclarés.",
                    static::class, $i, count($row), count($fields)
                ));
            }
        }

        if (!empty($this->params['on_duplicate'])) {
            if (!$this->supportsOnDuplicateKey()) {
                throw new \InvalidArgumentException(
                    static::class . ": 'on_duplicate' n'est pas supporté."
                );
            }
            if (!is_array($this->params['on_duplicate'])) {
                throw new \InvalidArgumentException(
                    static::class . ": 'on_duplicate' doit être un tableau."
                );
            }
        }
    }

    protected function normalizeValues(): array
    {
        $values = $this->params['values'] ?? [];

        if (!is_array($values) || empty($values)) {
            return [];
        }

        return is_array(reset($values)) ? $values : [$values];
    }

    // ---------------------------------------------------------------------
    //  Construction SQL (idempotente : reconstruit à chaque appel)
    // ---------------------------------------------------------------------

    protected function buildSql(): string
    {
        $sql = $this->keyword() . ' ';

        if ($this->supportsIgnore() && !empty($this->params['ignore'])) {
            $sql .= 'IGNORE ';
        }

        $sql .= 'INTO ' . $this->quoteIdentifier(DB::getPrefix() . $this->params['table']);

        $fields = $this->params['fields'];
        $rows   = $this->normalizeValues();

        $sql .= ' (' . implode(',', array_map(
            fn($f) => $this->quoteIdentifier($f),
            $fields
        )) . ') ';

        $rowPlaceholders = [];

        foreach ($rows as $rowIndex => $_) {
            $placeholders = array_map(
                fn($field) => ":{$field}_{$rowIndex}",
                $fields
            );
            $rowPlaceholders[] = '(' . implode(',', $placeholders) . ')';
        }

        $sql .= 'VALUES ' . implode(',', $rowPlaceholders);

        // Alias MySQL 8.0.19+ (uniquement si ON DUPLICATE présent)
        $alias = $this->resolveAlias();

        if ($alias !== null) {
            $sql .= ' AS ' . $this->quoteIdentifier($alias);
        }

        // ON DUPLICATE KEY UPDATE
        if ($this->supportsOnDuplicateKey() && !empty($this->params['on_duplicate'])) {
            $sql .= $this->buildOnDuplicateKeyClause($alias);
        }

        return $sql;
    }

    /**
     * Retourne l'alias à utiliser, ou null si non applicable.
     */
    protected function resolveAlias(): ?string
    {
        $alias = $this->params['alias'] ?? null;

        if (!$alias || empty($this->params['on_duplicate'])) {
            return null;
        }

        if (!$this->supportsAliasUpsert()) {
            return null; // MySQL 5.7 ou MariaDB : ignorer l'alias
        }

        return $alias;
    }

    protected function buildOnDuplicateKeyClause(?string $alias): string
    {
        $columns  = $this->params['on_duplicate'];
        $useAlias = $alias !== null;

        $updates = [];

        foreach ($columns as $key => $value) {
            if (is_int($key)) {
                // Format A : ['title', 'price'] → title = VALUES(title) ou alias.title
                $col  = $value;
                $qcol = $this->quoteIdentifier($col);

                $updates[] = $useAlias
                    ? "{$qcol} = " . $this->quoteIdentifier($alias) . ".{$qcol}"
                    : "{$qcol} = VALUES({$qcol})";
            } else {
                // Format B : ['visit_count' => 'visit_count + 1']
                $col  = $key;
                $expr = $value;

                $updates[] = $this->quoteIdentifier($col) . " = {$expr}";
            }
        }

        return ' ON DUPLICATE KEY UPDATE ' . implode(',', $updates);
    }

    // ---------------------------------------------------------------------
    //  Binding (inchangé)
    // ---------------------------------------------------------------------

    protected function bindStatement(Contracts\PreparedStatementInterface $stmt): void
    {
        $fields   = $this->params['fields'];
        $rows     = $this->normalizeValues();
        $bindings = [];
        
        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $i => $type) {
                if ($this->isRawExpression($type)) continue;
                $field = $fields[$i];
                $alias = "{$field}_{$rowIndex}";

                $value = is_subclass_of($type, DataType::class)
                    ? $type->getValue()
                    : $type;

                $bindings[':' . $alias] = [$value, $this->getDataType($type)];
            }
        }
        
        $stmt->bindAll($bindings)->execute();
        $this->statementExecuted = true;
    }

    // ---------------------------------------------------------------------
    //  API publique (inchangée)
    // ---------------------------------------------------------------------

    public function getInsertId(): int|string|null { return $this->insert_id; }
    public function getAffectedRows(): int { return $this->num_rows; }

    public function getInsertedIds(): array
    {
        $count = count($this->normalizeValues());
        if ($count === 0 || $this->insert_id === null) {
            return [];
        }
        $first = (int) $this->insert_id;
        return range($first, $first + $count - 1);
    }

    public function getIterator(): \Traversable
    {
        return new DBQueryIterator($this);
    }
}