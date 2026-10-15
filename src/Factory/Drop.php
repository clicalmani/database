<?php
namespace Clicalmani\Database\Factory;

use Clicalmani\Database\Builders\AbstractDdlStatement;
use Clicalmani\Database\Concerns\IfExists;

/**
 * DROP TABLE [IF EXISTS] `table`;
 */
class Drop extends AbstractDdlStatement
{
    use IfExists;

    protected function buildSql(): string
    {
        return 'DROP TABLE' . $this->ifExistsClause() . ' ' . $this->tableName();
    }
}