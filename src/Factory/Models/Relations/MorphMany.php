<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Support\Facades\Str;
use Override;

class MorphMany extends Relationship
{
    /**
     * @param Elegant $model        Le modèle parent (ex: Post)
     * @param string $relatedClass  Le modèle enfant (ex: Comment)
     * @param string $name          Le nom de la relation (ex: 'commentable')
     * @param string $idKey         [Optionnel] Clé ID (ex: 'commentable_id')
     * @param string $typeKey       [Optionnel] Clé Type (ex: 'commentable_type')
     */
    public function __construct(
        protected Elegant $model,
        protected string $relatedClass,
        protected string $name,
        protected ?string $idKey = null,
        protected ?string $typeKey = null
    ) {
        $this->idKey = $idKey ?: $name . '_id';
        $this->typeKey = $typeKey ?: $name . '_type';
    }

    public function get(): mixed
    {
        $related = new $this->relatedClass;
        $query = $related->newQuery();

        // SELECT * FROM comments WHERE commentable_id = 10 AND commentable_type = 'Post'
        $query->where("{$this->idKey} = ?", [$this->model->{$this->model->getKey()}]);
        $query->where("{$this->typeKey} = ?", [$this->model::class]);

        $this->result = $related->fetch($this->relatedClass);

        return $this->result;
    }
}