<?php
namespace Clicalmani\Database\Builders;

use Clicalmani\Core\Support\Facades\DB;
use Clicalmani\Database\Contracts\QueryExecutorInterface;
use Clicalmani\Database\DBQueryBuilder;
use Clicalmani\Database\QueryType;

/**
 * Base commune aux instructions DDL (CREATE, ALTER, DROP).
 *
 * Ces requêtes :
 *  - ne retournent pas de PDOStatement exploitable ;
 *  - ne supportent pas de bindings utilisateur (sauf cas exotiques) ;
 *  - s'exécutent via PDO::exec() ;
 *  - doivent valider strictement les identifiants (injection SQL).
 */
abstract class AbstractDdlStatement extends DBQueryBuilder
{
    public function __construct(
        protected $params,
        protected $options,
        QueryExecutorInterface $executor,
        QueryType $type,
    ) {
        parent::__construct($params, $options, $executor, $type);

        if (empty($this->params['table'])) {
            throw new \InvalidArgumentException(
                static::class . ": le paramètre 'table' est requis."
            );
        }

        $this->sql = $this->buildSql();
    }

    /**
     * Chaque builder DDL implémente sa construction SQL ici.
     */
    abstract protected function buildSql(): string;

    /**
     * Échappe un identifiant SQL (nom de table ou de colonne).
     * Ex: users -> `users`, `weird name` -> `weird name`
     */
    protected function quoteIdentifier(string $identifier): string
    {
        // Autorise les identifiants composés : `schema`.`table`
        if (str_contains($identifier, '.')) {
            return implode('.', array_map(
                fn($part) => $this->quoteIdentifier($part),
                explode('.', $identifier)
            ));
        }

        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * Nom complet de la table avec préfixe, déjà échappé.
     */
    protected function tableName(): string
    {
        return $this->quoteIdentifier(DB::getPrefix() . $this->params['table']);
    }

    /**
     * Hook par défaut : exécution simple via executor.
     * Les sous-classes peuvent surcharger si elles ont besoin de plusieurs statements.
     */
    protected function doQuery(): void
    {
        $this->status = $this->executor->execute($this->sql);

        $this->error_code = $this->executor->errno();
        $this->error_msg  = $this->executor->error();
    }

    public function getIterator(): \Traversable
    {
        return new \Clicalmani\Database\DBQueryIterator($this);
    }
}