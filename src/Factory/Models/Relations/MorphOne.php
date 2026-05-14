<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;

class MorphOne extends Relationship
{
    /**
     * @param Elegant $model        Le modèle parent (ex: User)
     * @param string $relatedClass  Le modèle enfant (ex: Image)
     * @param string $name          Le nom de la relation (ex: 'imageable')
     * @param string|null $idKey    Clé ID (ex: 'imageable_id')
     * @param string|null $typeKey  Clé Type (ex: 'imageable_type')
     */
    public function __construct(
        protected Elegant $model,
        protected string $relatedClass,
        protected string $name,
        protected ?string $idKey = null,
        protected ?string $typeKey = null
    ) {
        $this->idKey   = $idKey ?: $name . '_id';
        $this->typeKey = $typeKey ?: $name . '_type';
    }

    /**
     * Récupère l'unique modèle enfant
     * 
     * @return mixed
     */
    public function get(): mixed
    {
        /** @var \Clicalmani\Database\Factory\Models\Elegant */
        $related = new $this->relatedClass;
        $query = $related->newQuery();

        // SELECT * FROM images WHERE imageable_id = 1 AND imageable_type = 'App\Models\User' LIMIT 1
        $query->where("{$this->idKey} = ?", [$this->model->{$this->model->getKey()}]);
        $query->where("{$this->typeKey} = ?", [$this->model::class]);

        $this->result = $related->fetchOne($this->relatedClass);

        return $this->result;
    }
}