<?php
namespace Clicalmani\Database\SubQueries;

use Clicalmani\Database\QueryInterface;

class Exists extends DBSubQuery implements SubQueryInterface
{
    public function __construct(
        protected QueryInterface $query,
        protected \Closure $callback
    )
    {
        parent::__construct($query, $callback);
        $this->backup();
        $this->call();
    }

    public function __invoke(mixed ...$args): QueryInterface
    {
        $this->restore();
        return $this->query->where('EXISTS (' . $this->builder->getSQL() . ')', $args[0] ?? 'AND', $this->options);
    }
}