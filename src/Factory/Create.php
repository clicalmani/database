<?php
namespace Clicalmani\Database\Factory;

use Clicalmani\Database\Builders\AbstractDdlStatement;
use Clicalmani\Database\Concerns\IfNotExists;

/**
 * Class Create
 *
 * Database table creation.
 *
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Create extends AbstractDdlStatement
{
    use IfNotExists;

    protected function buildSql(): string
    {
        $definitions = $this->params['definition'] ?? [];

        if (empty($definitions) || !is_array($definitions)) {
            throw new \InvalidArgumentException(
                "Create: 'definition' doit être un tableau non vide de définitions de colonnes/index."
            );
        }

        $sql = 'CREATE TABLE' . $this->ifNotExistsClause()
            . ' ' . $this->tableName()
            . " (\n\t"
            . implode(",\n\t", array_map('strval', $definitions))
            . "\n)";

        $sql .= $this->buildTableOptions();

        return $sql;
    }

    private function buildTableOptions(): string
    {
        $options = [];

        // Ordre canonique MySQL
        if (!empty($this->params['engine'])) {
            $options[] = 'ENGINE=' . $this->validateIdentifier($this->params['engine'], 'engine');
        }

        if (!empty($this->params['charset'])) {
            $options[] = 'DEFAULT CHARACTER SET=' . $this->validateIdentifier($this->params['charset'], 'charset');
        }

        if (!empty($this->params['collate'])) {
            $options[] = 'DEFAULT COLLATE=' . $this->validateIdentifier($this->params['collate'], 'collate');
        }

        if (isset($this->params['auto_increment'])) {
            $options[] = 'AUTO_INCREMENT=' . (int) $this->params['auto_increment'];
        }

        if (isset($this->params['comment'])) {
            $options[] = 'COMMENT=' . var_export((string) $this->params['comment'], true);
        }

        return $options ? ' ' . implode(' ', $options) : '';
    }

    /**
     * Valide qu'un identifiant ne contient que des caractères sûrs.
     * Utilisé pour ENGINE, CHARSET, COLLATE.
     */
    private function validateIdentifier(mixed $value, string $label): string
    {
        $value = (string) $value;

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
            throw new \InvalidArgumentException(
                "Create: '$label' contient des caractères invalides: '$value'"
            );
        }

        return $value;
    }
}