<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;

class MorphTo extends Relationship
{
    /**
     * @param Elegant $model  L'instance du commentaire actuel
     * @param string $name    Le nom de la relation (ex: 'commentable')
     */
    public function __construct(
        protected Elegant $model,
        protected string $name
    ) {}

    public function get(): mixed
    {
        $idKey   = $this->name . '_id';
        $typeKey = $this->name . '_type';

        $parentId   = $this->model->$idKey;
        $parentType = $this->model->$typeKey;

        if (!$parentId || !$parentType) {
            return null;
        }

        // Dynamisme : on instancie la classe stockée en base (ex: Post ou Video)
        $parentModel = new $parentType;
        $query = $parentModel->newQuery();
        $query->where($parentModel->getKey() . " = ?", [$parentId]);
        
        // On récupère le parent par son ID
        $this->result = $parentModel->fetchOne();

        return $this->result;
    }
}