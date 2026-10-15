<?php
namespace Clicalmani\Database;

use Clicalmani\Core\Support\Facades\DB;
use Clicalmani\Database\Concerns\QuotesIdentifiers;

/**
 * Class Truncate
 *
 * TRUNCATE TABLE `table`;
 *
 * ⚠️ ATTENTION :
 *  - TRUNCATE est un DDL implicite : il COMMIT la transaction en cours.
 *    Il ne peut PAS être annulé par ROLLBACK.
 *  - Il réinitialise AUTO_INCREMENT à 1.
 *  - Il ne déclenche PAS les triggers ON DELETE.
 *  - Il échoue si la table est référencée par une FOREIGN KEY
 *    (sauf si FOREIGN_KEY_CHECKS=0).
 *
 * Pour vider sans réinitialiser AUTO_INCREMENT, préférer DELETE FROM.
 *
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class Truncate extends DBQueryBuilder implements \IteratorAggregate
{
	use QuotesIdentifiers;
	
    public function __construct(
        protected $params,
        protected $options,
        Contracts\QueryExecutorInterface $executor,
        QueryType $type,
    ) {
        parent::__construct($params, $options, $executor, $type);

        if (empty($this->params['table'])) {
            throw new \InvalidArgumentException("Truncate: 'table' est requis.");
        }

        $this->sql = 'TRUNCATE TABLE '
            . $this->quoteIdentifier(DB::getPrefix() . $this->params['table']);
    }

    public function getIterator(): \Traversable
    {
        return new DBQueryIterator($this);
    }
}