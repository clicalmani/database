<?php
namespace Clicalmani\Database\SubQueries;

use Clicalmani\Database\BuilderInterface;
use Clicalmani\Database\QueryInterface;
use Clicalmani\Database\Select;

class DBSubQuery
{
    protected array $options = [];
    protected BuilderInterface $builder;

    private array $query_params = [];
    private array $query_options = [];

    public function __construct(
        protected QueryInterface $query,
        protected \Closure $callback
    )
    {
        // Backup query
        $this->backup();
    }

    /**
     * Returns the subquery options.
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Returns the query builder.
     * @return BuilderInterface
     */
    public function getBuilder(): BuilderInterface
    {
        return $this->builder;
    }

    /**
     * Backups sub-query data
     */
    public function backup(): void
    {
        $this->query_params = $this->query->getParams();
        $this->query_options = $this->query->getOptions();
    }

    /**
     * Restores the sub-query data
     */
    public function restore(): void
    {
        $this->query->setParams($this->query_params);
        $this->query->setOptions($this->query_options);
    }

    /**
     * Execute the sub-query.
     */
    public function call(): void
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

    /**
     * Returns the main query.
     * @return QueryInterface
     */
    public function getQuery(): QueryInterface
    {
        return $this->query;
    }
}