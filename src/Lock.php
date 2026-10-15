<?php
namespace Clicalmani\Database;

use Clicalmani\Core\Support\Facades\DB;

/**
 * Class Lock
 *
 * LOCK TABLES `table` [READ | READ LOCAL | WRITE | LOW_PRIORITY WRITE];
 *
 * Options :
 *  - lock_type     : 'READ', 'READ LOCAL', 'WRITE', 'LOW_PRIORITY WRITE'
 *  - disable_keys  : si vrai, exécute ALTER TABLE ... DISABLE KEYS AVANT le lock
 *
 * ⚠️ IMPORTANT :
 *  - Un appel à LOCK TABLES libère implicitement tous les verrous précédents.
 *  - Toujours terminer par UNLOCK TABLES explicite.
 *  - Ne pas exécuter dans une transaction.
 *
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Lock extends Builders\AbstractLockStatement
{
    protected function buildStatements(): void
    {
        if (empty($this->params['table'])) {
            throw new \InvalidArgumentException("Lock: 'table' est requis.");
        }

        $table    = $this->quoteIdentifier(DB::getPrefix() . $this->params['table']);
        $lockType = $this->validateLockType($this->params['lock_type'] ?? null);

        // Ordre : DISABLE KEYS d'abord, puis LOCK
        if (!empty($this->params['disable_keys'])) {
            $this->statements[] = "ALTER TABLE {$table} DISABLE KEYS";
        }

        $this->statements[] = "LOCK TABLES {$table} {$lockType}";
    }

    protected function validateLockType(?string $type): string
    {
        $type = strtoupper(trim($type ?? 'WRITE'));

        $allowed = ['READ', 'READ LOCAL', 'WRITE', 'LOW_PRIORITY WRITE'];

        if (!in_array($type, $allowed, true)) {
            throw new \InvalidArgumentException("Lock: type '$type' invalide.");
        }

        return $type;
    }
}