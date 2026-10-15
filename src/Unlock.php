<?php
namespace Clicalmani\Database;

use Clicalmani\Core\Support\Facades\DB;

/**
 * Class Unlock
 *
 * Libère les verrous de table de la session courante.
 *
 * ⚠️ IMPORTANT :
 *  - UNLOCK TABLES libère TOUS les verrous détenus par la session.
 *  - Il doit être exécuté en DEHORS de toute transaction (erreur MySQL sinon).
 *  - Un UNLOCK sans LOCK préalable est un no-op silencieux (aucune erreur).
 *  - Si 'enable_keys' est demandé, l'ALTER TABLE suit immédiatement
 *    l'UNLOCK, dans cet ordre (libérer les verrous le plus tôt possible).
 *
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Unlock extends Builders\AbstractLockStatement
{
    protected function buildStatements(): void
    {
        // 1. UNLOCK TABLES en premier (libère les verrous le plus tôt possible)
        $this->statements[] = 'UNLOCK TABLES';

        // 2. ALTER TABLE ... ENABLE KEYS ensuite (si demandé)
        if (isset($this->params['enable_keys'])) {
            if (empty($this->params['table'])) {
                throw new \InvalidArgumentException(
                    "Unlock: 'table' est requis quand 'enable_keys' est demandé."
                );
            }

            $table = $this->quoteIdentifier(DB::getPrefix() . $this->params['table']);
            $this->statements[] = "ALTER TABLE {$table} ENABLE KEYS";
        }
    }
}