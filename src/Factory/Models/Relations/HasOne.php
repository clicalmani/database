<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Support\Facades\Str;

class HasOne extends Relationship
{
    /**
     * @param Elegant $model        Le modèle parent actuel (ex: User)
     * @param string $relatedClass  La classe du modèle enfant (ex: Profile)
     * @param string|null $foreignKey La clé étrangère dans la table enfant (user_id)
     * @param string|null $localKey   La clé primaire dans la table parente (id)
     */
    public function __construct(
        protected Elegant $model,
        protected string $relatedClass,
        protected ?string $foreignKey = null,
        protected ?string $localKey = null
    ) {
        // Par défaut : user_id
        $this->foreignKey = $foreignKey ?: Str::singularize($this->model->getTable()) . '_id';
        
        // Par défaut : id (clé primaire du parent)
        $this->localKey = $localKey ?: $this->model->getKey();
    }

    /**
     * Récupère le modèle enfant unique
     * 
     * @return mixed
     */
    public function get(): mixed
    {
        $related = new $this->relatedClass;
        $query = $related->newQuery();

        // Requête : SELECT * FROM profiles WHERE user_id = [ID de l'utilisateur] LIMIT 1
        $query->where("{$this->foreignKey} = ?", [$this->model->{$this->localKey}]);

        $this->result = $related->fetchOne($this->relatedClass);

        return $this->result;
    }
}