<?php
namespace Clicalmani\Database\SubQueries;

use Clicalmani\Database\DBQuery;
use Clicalmani\Database\DBQueryBuilder;
use Clicalmani\Database\Select;

class DBSubQuery
{
    protected array $options;
    protected DBQueryBuilder $builder;

    private array $query_params = [];
    private array $query_options = [];

    public function __construct(
        protected DBQuery $query,
        protected \Closure $callback
    )
    {
        // Backup query
        $this->backup();
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getBuilder()
    {
        return $this->builder;
    }

    public function backup()
    {
        $this->query_params = $this->query->getParams();
        $this->query_options = $this->query->getOptions();
    }

    public function restore()
    {
        $this->query->setParams($this->query_params);
        $this->query->setOptions($this->query_options);
    }

    public function call()
    {
        // Set the parameters for the subquery
		$this->query->setParams([]);
		$this->query->setOptions([]);
        
		($this->callback)($this->query);

		// Execute the subquery and get the builder
		$this->options = $this->query->getOptions(); // Backup the subquery options to merge them later with the main query options
		$this->builder = new Select(
            $this->query->getParams(), 
            array_merge($this->query->getOptions(), $this->options)
        );
    }

    public function getQuery()
    {
        return $this->query;
    }
}