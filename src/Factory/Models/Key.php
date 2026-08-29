<?php
namespace Clicalmani\Database\Factory\Models;

/**
 * Represents an ORM model key (single or composite).
 *
 * This immutable class encapsulates column name => value pairs, handles table alias qualification,
 * generates SQL conditions, and resolves key values from raw query result sets.
 *
 * @package Clicalmani\Database\Factory\Models
 * @author @clicalmani
 */
final class Key
{
    /**
     * Associative pairs of column name => value.
     *
     * Values can be `null` when a key structure is defined but not yet instantiated with values.
     *
     * @var array<string, mixed>
     */
    private readonly array $pairs;

    /**
     * Private constructor to enforce immutability and named constructor usage.
     *
     * @param array<string, mixed> $pairs Column => value pairs.
     * @param string|null $alias Optional table or subquery alias.
     */
    private function __construct(
        array $pairs,
        private readonly ?string $alias = null,
    ) {
        $this->pairs = $pairs;
    }

    /**
     * Constructs a key instance from column name(s) only.
     *
     * Useful when the key definition is known but its values are not yet resolved
     * (e.g., primary key definition on a model class).
     *
     * @param string|array<int, string> $name Column name(s).
     * @param string|null $alias Optional table alias.
     * @return self
     */
    public static function fromName(string|array $name, ?string $alias = null): self
    {
        $names = is_array($name) ? $name : [$name];
        return new self(array_fill_keys($names, null), $alias);
    }

    /**
     * Constructs a key instance from value(s), associating them with column names.
     *
     * If `$value` is a string containing pipe delimiters (`|`), it will automatically
     * be split into an array to construct a composite key.
     *
     * @param string|array<int, mixed> $value Key value(s) or pipe-separated string.
     * @param string|array<int, string>|null $name Corresponding column name(s).
     * @return self
     * @throws \InvalidArgumentException If the number of names does not match the number of values.
     */
    public static function fromValue(string|array $value, string|array|null $name = null): self
    {
        $values = is_array($value) ? $value : explode('|', $value);

        if ($name !== null) {
            $names = is_array($name) ? $name : [$name];
            
            if (count($names) !== count($values)) {
                throw new \InvalidArgumentException('Le nombre de noms et de valeurs de clé ne correspond pas.');
            }

            return new self(array_combine($names, $values));
        }

        return new self($values);
    }

    /**
     * Guesses a pair of relationship keys (foreign key, referenced key)
     * following standard ORM conventions (`relatedTable_id` / `id`).
     *
     * Fallbacks to explicitly provided keys when available.
     *
     * @param string|null $foreignKey Explicit foreign key (current table column).
     * @param string|null $originalKey Explicit referenced key (related table column).
     * @param string $currentAlias Current table alias.
     * @param string $currentTableSingular Current table singular name.
     * @param string|null $relatedAlias Related table alias.
     * @param string|null $relatedTableSingular Related table singular name.
     * @return array{0: self, 1: self} Tuple containing [Foreign Key, Referenced Key].
     */
    public static function guessRelationship(
        ?string $foreignKey,
        ?string $originalKey,
        string $currentAlias,
        string $currentTableSingular,
        ?string $relatedAlias = null,
        ?string $relatedTableSingular = null,
    ): array {
        $hasRelated = null !== $relatedTableSingular;

        $currentAlias = strtolower($currentAlias);
        $relatedAlias = $relatedAlias !== null ? strtolower($relatedAlias) : $currentAlias;
        $relatedTableSingular ??= $currentTableSingular;

        // 1. Both keys are explicitly provided.
        if (null !== $foreignKey && null !== $originalKey) {
            return [self::fromName($foreignKey), self::fromName($originalKey)];
        }

        // 2. Self-join or single explicit key provided.
        if (null !== $foreignKey && null === $originalKey) {
            return [self::fromName($foreignKey), self::fromName($foreignKey)];
        }

        // 3. Deduce using standard conventions for related models.
        if ($hasRelated) {
            $fk = self::fromName($relatedTableSingular . '_id')->withAlias($currentAlias ?: null);
            $pk = self::fromName('id')->withAlias($relatedAlias ?: null);

            return [$fk, $pk];
        }

        // 4. Default fallback when no explicit related model is provided.
        $fk = self::fromName($relatedTableSingular . '_id');
        $pk = self::fromName('id')->withAlias($currentAlias ?: null);

        return [$fk, $pk];
    }

    /**
     * Returns a new instance populated with the given values.
     *
     * @param string|array<int, mixed> $value Value(s) to bind to the key columns.
     * @return self
     * @throws \InvalidArgumentException If value count does not match column count.
     */
    public function withValues(string|array $value): self
    {
        $values = is_array($value) ? array_values($value) : [$value];
        $names  = array_keys($this->pairs);

        if (count($names) !== count($values)) {
            throw new \InvalidArgumentException('Le nombre de valeurs ne correspond pas au nombre de colonnes de la clé.');
        }

        return new self(array_combine($names, $values), $this->alias);
    }

    /**
     * Returns a new instance associated with the given table alias.
     *
     * @param string|null $alias
     * @return self
     */
    public function withAlias(?string $alias): self
    {
        return new self($this->pairs, $alias);
    }

    /**
     * Determines whether the key is composite (contains more than one column).
     *
     * @return bool
     */
    public function isComposite(): bool
    {
        return count($this->pairs) > 1;
    }

    /**
     * Checks if any column value in the key is `null`.
     *
     * @return bool
     */
    public function hasNull(): bool
    {
        return in_array(null, $this->pairs, true);
    }

    /**
     * Checks if all column values in the key are `null`.
     *
     * @return bool
     */
    public function isNull(): bool
    {
        foreach ($this->pairs as $value) {
            if (null !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retrieves column names.
     *
     * @param bool $qualified If `true`, qualifies column names with the table alias (`alias.column`).
     * @return string[]
     */
    public function names(bool $qualified = false): array
    {
        $names = array_keys($this->pairs);

        if (!$qualified || !$this->alias) return $names;

        return array_map(fn($n) => "{$this->alias}.{$n}", $names);
    }

    /**
     * Retrieves a indexed array of key values.
     *
     * @return array<int, mixed>
     */
    public function values(): array
    {
        return array_values($this->pairs);
    }

    /**
     * Retrieves the single column name for scalar (non-composite) keys.
     *
     * @param bool $qualified If `true`, qualifies the column name with the alias.
     * @return string
     * @throws \LogicException If called on a composite key.
     */
    public function scalarName(bool $qualified = false): string
    {
        $this->assertNotComposite('name');
        return $this->names($qualified)[0];
    }

    /**
     * Retrieves the single value for scalar (non-composite) keys.
     *
     * @return mixed
     * @throws \LogicException If called on a composite key.
     */
    public function scalarValue(): mixed
    {
        $this->assertNotComposite('value');
        return array_values($this->pairs)[0];
    }

    /**
     * Generates a prepared SQL WHERE clause condition and its corresponding bindings.
     *
     * @param string $operator SQL comparison operator (e.g., `=`, `>`, `LIKE`).
     * @return array{0: string, 1: array<int, mixed>} Tuple containing [SQL Condition, Parameter Bindings].
     */
    public function toSqlCondition(string $operator = '='): array
    {
        $conditions = [];
        $bindings   = [];

        foreach ($this->pairs as $name => $value) {
            $qualified    = $this->alias ? "{$this->alias}.{$name}" : "`$name`";
            $conditions[] = "{$qualified} {$operator} ?";
            $bindings[]   = $value;
        }
        
        return [implode(' AND ', $conditions), $bindings];
    }

    public function toValue()
    {
        return $this->isComposite() ? $this->values(): $this->scalarValue();
    }

    /**
     * Exports the key pairs as an associative array (`column => value`).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->pairs;
    }

    /**
     * Resolves key values from a raw SQL database row array.
     *
     * Handles qualified columns (`alias.column`), plain columns (`column`),
     * or jointure suffix matches.
     *
     * @param array<string, mixed> $row Associative array representing a database row.
     * @return self New instance with extracted values.
     * @throws \OutOfBoundsException If any key column is missing from the result set.
     */
    public function fromResult(array $row): self
    {
        $values = [];

        foreach (array_keys($this->pairs) as $name) {
            $value = $row[$name]                              
                ?? $row[$this->alias . '.' . $name]            
                ?? $this->findByUnqualifiedMatch($row, $name); 

            if ($value === null && !array_key_exists($name, $row)) {
                throw new \OutOfBoundsException(
                    sprintf("Impossible de retrouver la colonne de clé [%s] dans le résultat fourni.", $name)
                );
            }

            $values[$name] = $value;
        }

        return new self($values, $this->alias);
    }

    /**
     * Compares the current key instance with another key instance.
     *
     * @param self $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->pairs === $other->pairs && $this->alias === $other->alias;
    }

    /**
     * Searches for a key ending with `.columnName` in the database row.
     *
     * @param array<string, mixed> $row Database row.
     * @param string $name Target column name.
     * @return array{0: bool, 1: mixed} Tuple containing [Success flag, Found value].
     */
    private function findByUnqualifiedMatch(array $row, string $name): mixed
    {
        foreach ($row as $column => $value) {
            if (str_ends_with($column, ".{$name}")) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Asserts that the key is not composite before performing scalar operations.
     *
     * @param string $what Description of the scalar component being requested.
     * @return void
     * @throws \LogicException If the key contains multiple columns.
     */
    private function assertNotComposite(string $what): void
    {
        if ($this->isComposite()) {
            throw new \LogicException("Impossible de réduire une clé composite à un(e) seul(e) {$what}.");
        }
    }

    /**
     * Converts the key to its string representation.
     *
     * Concatenates values with `|` if the key is composite.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->isComposite()
            ? implode('|', $this->values())
            : (string) $this->scalarValue();
    }

    /**
     * Safely converts any value type to string without runtime errors.
     *
     * @param mixed $val
     * @return string
     */
    private function stringifyValue(mixed $val): string
    {
        if ($val instanceof \BackedEnum) {
            return (string) $val->value;
        }

        if ($val instanceof \UnitEnum) {
            return $val->name;
        }

        if ($val instanceof \Stringable || is_scalar($val)) {
            return (string) $val;
        }

        if (null === $val) {
            return '';
        }

        return serialize($val);
    }
}