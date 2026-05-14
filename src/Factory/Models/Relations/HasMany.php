<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Support\Facades\Str;
use Override;

class HasMany extends Relationship
{
    /**
     * @param Elegant $model        L'instance du modèle parent (ex: Department)
     * @param string $relatedClass  La classe du modèle enfant (ex: Employee)
     * @param string|null $foreignKey  La clé étrangère (ex: department_id)
     * @param string|null $localKey    La clé locale du parent (ex: id)
     */
    public function __construct(
        protected Elegant $model,
        protected string $relatedClass,
        protected ?string $foreignKey = null,
        protected ?string $localKey = null
    ) {
        // Si la clé étrangère n'est pas fournie, on la devine (ex: department_id)
        $this->foreignKey = $foreignKey ?: Str::singularize($this->model->getTable()) . '_id';
        $this->localKey   = $localKey ?: $this->model->getKey();
    }

    /**
     * Récupère la collection des modèles enfants
     * 
     * @return mixed
     */
    public function get(): mixed
    {
        $related = new $this->relatedClass;
        $query = $related->newQuery();

        // On filtre : WHERE department_id = [ID du département actuel]
        $query->where("{$this->foreignKey} = ?", [$this->model->{$this->localKey}]);

        // On retourne la collection de résultats
        $this->result = $related->fetch($this->relatedClass);

        return $this->result;
    }
}