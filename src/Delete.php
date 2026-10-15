<?php
namespace Clicalmani\Database;

/**
 * Class Delete
 *
 * DELETE [signatures] FROM table [PARTITION (...)] [WHERE ...] [ORDER BY ...] [LIMIT n];
 * DELETE [signatures] FROM t1 USING t1 JOIN t2 WHERE ...;   (multi-table)
 *
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Delete extends Builders\AbstractDeleteStatement
{
    protected function keyword(): string { return 'DELETE'; }
    // supportsOrderByLimit() et supportsMultiTable() → true par défaut
}