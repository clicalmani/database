<?php
namespace Clicalmani\Database;

/**
 * Class Update
 *
 * UPDATE [LOW_PRIORITY] [IGNORE] table SET ... WHERE ...;
 *
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Update extends Builders\AbstractUpdateStatement
{
    protected function keyword(): string { return 'UPDATE'; }
    // supportsLowPriority() et supportsIgnore() → true par défaut
}