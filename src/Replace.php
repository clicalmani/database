<?php
namespace Clicalmani\Database;

/**
 * Class Replace
 *
 * REPLACE INTO ... (sémantique MySQL : DELETE + INSERT si collision de clé).
 *
 * ⚠️ ATTENTION :
 *  - REPLACE est destructif : les colonnes non mentionnées sont remises
 *    à leur valeur par défaut.
 *  - Les triggers ON DELETE se déclenchent.
 *  - AUTO_INCREMENT peut sauter des IDs.
 *  - Ne supporte PAS le modificateur IGNORE.
 *
 * Pour une sémantique non destructive, préférer :
 *   INSERT ... ON DUPLICATE KEY UPDATE
 *
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Replace extends Builders\AbstractInsertStatement
{
    protected function keyword(): string
    {
        return 'REPLACE';
    }
}