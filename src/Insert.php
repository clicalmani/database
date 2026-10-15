<?php
namespace Clicalmani\Database;

/**
 * Class Insert
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Insert extends Builders\AbstractInsertStatement
{
    protected function keyword(): string { return 'INSERT'; }
    protected function supportsIgnore(): bool { return true; }
    protected function supportsOnDuplicateKey(): bool { return true; }
}
