<?php
namespace Clicalmani\Database\SubQueries;

use Clicalmani\Database\QueryInterface;

class WithExists extends DBSubQuery implements SubQueryInterface
{
    public function __construct(QueryInterface $query, \Closure $callback)
    {
        parent::__construct($query, $callback);
        $this->backup();
		$this->call();
    }

    public function __invoke(mixed ...$args): QueryInterface
    {
        $raw = 'EXISTS (' . $this->builder->getSQL() . ') AS ' . $args[0];
        $this->restore();
        $select = [$this->query->getParam('fields') ?? '', $raw];
        $this->query->setOptions(array_merge($this->options, $this->query->getOptions()));
        return $this->query->set('fields', join(', ', collect($select)->filter(fn($value) => !!$value)->toArray()));
    }
}