<?php
namespace Clicalmani\Database\Builders;

use Clicalmani\Core\Support\Facades\DB;
use Clicalmani\Database\Concerns\QuotesIdentifiers;
use Clicalmani\Database\Contracts;
use Clicalmani\Database\DBQueryBuilder;
use Clicalmani\Database\DBQueryIterator;
use Clicalmani\Database\QueryType;

/**
 * Base commune aux instructions LOCK / UNLOCK TABLES.
 *
 * Ces commandes :
 *  - s'exécutent en dehors de toute transaction (erreur MySQL sinon) ;
 *  - peuvent être composées de plusieurs statements ;
 *  - ne retournent aucun PDOStatement.
 */
abstract class AbstractLockStatement extends DBQueryBuilder implements \IteratorAggregate
{
    use QuotesIdentifiers;

    /** @var string[] */
    protected array $statements = [];

    public function __construct(
        protected $params,
        protected $options,
        Contracts\QueryExecutorInterface $executor,
        QueryType $type,
    ) {
        parent::__construct($params, $options, $executor, $type);

        $this->buildStatements();
        $this->sql = implode(";\n", $this->statements);
    }

    /**
     * Construit la liste des statements à exécuter.
     */
    abstract protected function buildStatements(): void;

    protected function doQuery(): void
    {
        $this->status = true;

        foreach ($this->statements as $index => $sql) {
            if (!$this->executor->execute($sql)) {
                $this->status     = false;
                $this->error_code = $this->executor->errno();
                $this->error_msg  = sprintf(
                    "Statement #%d failed: %s — %s",
                    $index,
                    $sql,
                    $this->executor->error()
                );
                return;
            }
        }

        $this->error_code = 0;
        $this->error_msg  = '';
    }

    public function getIterator(): \Traversable
    {
        return new DBQueryIterator($this);
    }
}