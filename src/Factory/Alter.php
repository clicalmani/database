<?php
namespace Clicalmani\Database\Factory;

use Clicalmani\Database\Builders\AbstractDdlStatement;

/**
 * Class Alter
 *
 * ALTER TABLE `table` <operations>;
 *
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Alter extends AbstractDdlStatement
{
    protected function buildSql(): string
    {
        $definitions = $this->params['definition'] ?? [];
		
        if (empty($definitions) || !is_array($definitions)) {
            throw new \InvalidArgumentException(
                "Alter: 'definition' doit être un tableau non vide d'opérations."
            );
        }

        $clauses = array_map(
            fn($d) => trim((string) $d),
            $definitions
        );

        $clauses = array_filter($clauses, fn($c) => $c !== '');

        if (empty($clauses)) {
            throw new \InvalidArgumentException(
                "Alter: aucune opération valide après normalisation."
            );
        }

        return 'ALTER TABLE ' . $this->tableName()
            . "\n    " . implode(",\n    ", $clauses);
    }
}