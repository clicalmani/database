<?php
namespace Clicalmani\Database;

/**
 * Class Select
 *
 * SELECT [DISTINCT] [SQL_CALC_FOUND_ROWS] colonnes FROM ... WHERE ...;
 *
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Select extends Builders\AbstractSelectStatement
{
    protected function keyword(): string { return 'SELECT'; }
    // supportsDistinct() et supportsCalcFoundRows() → true par défaut
}