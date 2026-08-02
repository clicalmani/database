<?php
namespace Clicalmani\Database\SubQueries;

use Clicalmani\Database\QueryInterface;

class SubWhere extends DBSubQuery implements SubQueryInterface
{
    public function __construct(QueryInterface $query, \Closure $callback)
    {
        parent::__construct($query, $callback);
        $this->backup();
		$this->call();
    }

    public function __invoke(mixed ...$args): QueryInterface
    {
        $this->restore();
        return $this->query->where($args[0] . " {$args[1]} " . '(' . $this->builder->getSQL() . ')', $args[2] ?? 'AND', $this->options);
    }
}