<?php
namespace Clicalmani\Database\Concerns;

trait QuotesIdentifiers
{
    /**
     * Échappe un identifiant SQL. Gère les identifiants composés (schema.table).
     */
    protected function quoteIdentifier(string $identifier): string
    {
        if (str_contains($identifier, '.')) {
            return implode('.', array_map(
                fn($part) => $this->quoteIdentifier($part),
                explode('.', $identifier)
            ));
        }

        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}