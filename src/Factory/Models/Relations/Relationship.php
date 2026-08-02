<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Database\Factory\Models\ModelInterface;
use Clicalmani\Foundation\Collection\Collection;
use Clicalmani\Foundation\Collection\CollectionInterface;

abstract class Relationship implements \JsonSerializable
{
    protected Elegant $model;
    protected \Clicalmani\Database\DBQuery $query;
    protected ModelInterface|CollectionInterface|null $result = null;
    protected array|\Closure|null $default = null;

    abstract public function get(?string $fields = '*'): mixed;

    /**
     * Retrieve parent keys for eager-loading
     * 
     * @param Elegant[] $models
     * @return string[]
     */
    abstract public function getParentKeys(array $models): array;

    /**
     * Execute eager-loading request
     * 
     * @param string[] $keys
     * @return CollectionInterface
     */
    abstract public function getEager(array $keys): CollectionInterface;

    /**
     * Assign results to models
     * 
     * @param Elegant[] $models
     * @param CollectionInterface $results
     * @param string $relation
     * @return array
     */
    abstract public function match(array $models, CollectionInterface $results, string $relation): void;

    /**
     * Load nested relations
     * 
     * @param Elegant[] $results
     * @param string $relation
     * @return void
     */
    public function loadNestedRelations(CollectionInterface $results, string $relation) : void
    {
        $this->match(
            $results->toArray(), $this->getEager(
                $this->getParentKeys($results->toArray())
            ), $relation
        );
    }

    public function count()
    {
        return $this->get()->count();
    }

    public function jsonSerialize(): mixed
    {
        return $this->get();
    }

    /**
     * Récupère les clés du modèle parent
     */
    protected function getModelKeys(array $models, string $key): array
    {
        $keys = [];
        foreach ($models as $model) {
            $k = $model->{$key};
            if ($k) {
                $keys[] = $k;
            }
        }
        return array_unique($keys);
    }

    /**
     * Récupère le type de la classe parente
     */
    protected function getParentType(array $models, string $typeKey): string
    {
        $types = [];
        foreach ($models as $model) {
            if (isset($model->{$typeKey})) {
                $types[] = $model->{$typeKey};
            }
        }
        $types = array_unique($types);
        return count($types) === 1 ? $types[0] : null;
    }

    /**
     * Groupe les résultats par type pour les relations polymorphiques
     */
    protected function groupByType(CollectionInterface $results, string $typeKey): array
    {
        $grouped = [];
        foreach ($results as $result) {
            $type = $result->{$typeKey};
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $result;
        }
        return $grouped;
    }

    public function __call($name, $arguments)
    {
        if ( ! method_exists($this->query, $name) ) {
            throw new \BadMethodCallException("Method {$name} does not exist on the query builder.");
        }

        $this->query->{$name}(...$arguments);
        return $this;
    }
}