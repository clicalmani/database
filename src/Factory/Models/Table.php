<?php

namespace Clicalmani\Database\Factory\Models;

/**
 * Represents a database table definition, encapsulating its name, optional alias,
 * and prefix handling.
 *
 * @package Clicalmani\Database\Factory\Models
 * @author @clicalmani
 */
final class Table implements \Stringable
{
    /**
     * Clean table name without alias.
     */
    private readonly string $name;

    /**
     * Explicit table alias defined in the SQL declaration (if any).
     */
    private readonly ?string $alias;

    /**
     * Private constructor to enforce immutability and named constructor usage.
     *
     * Parse table names formatted like "table", "table alias", or "table AS alias".
     *
     * @param string $rawName Raw table definition string.
     */
    private function __construct(string $rawName)
    {
        $normalized = preg_replace('/\s+AS\s+/i', ' ', trim($rawName));
        $parts      = preg_split('/\s+/', $normalized, 2);

        $this->name  = $parts[0] ?? '';
        $this->alias = isset($parts[1]) && $parts[1] !== '' ? $parts[1] : null;
    }

    /**
     * Creates a new Table instance from a raw table name definition.
     *
     * @param string $name Raw table name (e.g., "users", "users u", or "users AS u").
     * @return self
     */
    public static function from(string $name): self
    {
        return new self($name);
    }

    /**
     * Returns the table alias if explicitly provided, or falls back to
     * the prefixed table name.
     *
     * @return string
     */
    public function alias(): string
    {
        return $this->alias ?? (env('DB_TABLE_PREFIX', '') . $this->name);
    }

    /**
     * Determines whether the table has an explicit alias defined.
     *
     * @return bool
     */
    public function isAliased(): bool
    {
        return $this->alias !== null;
    }

    /**
     * Backwards compatibility alias for `isAliased()`.
     *
     * @deprecated Use isAliased() instead.
     * @return bool
     */
    public function alised(): bool
    {
        return $this->isAliased();
    }

    /**
     * Returns the raw table name without prefixes or aliases.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns the full table declaration as formatted for SQL queries (e.g., "users u" or "users").
     *
     * @return string
     */
    public function withAlias(): string
    {
        if ($this->isAliased()) {
            return "{$this->name} {$this->alias}";
        }

        return $this->name;
    }

    /**
     * Converts the Table object to its string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->withAlias();
    }
}