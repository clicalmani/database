<?php
namespace Clicalmani\Database\SubQueries;

use Clicalmani\Database\DBQuery;

class NotExists extends DBSubQuery
{
    public function __construct(
        protected DBQuery $query,
        protected \Closure $callback
    )
    {
        parent::__construct($query, $callback);
        $this->backup();
        $this->call();
    }

    public function __invoke(?string $boolean = 'AND')
    {
        $this->restore();
        return $this->query->where('NOT EXISTS (' . $this->builder->getSQL() . ')', $boolean, $this->options);
    }
}