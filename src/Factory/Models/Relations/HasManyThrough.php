<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Support\Facades\Str;

class HasManyThrough extends Relationship
{
    /**
     * @param Elegant $model           Le modèle actuel (ex: Project)
     * @param string $farModelClass    Le modèle cible distant (ex: User)
     * @param string $throughModelClass Le modèle intermédiaire (ex: Task)
     * @param string|null $firstKey    Clé sur le modèle intermédiaire (project_id)
     * @param string|null $secondKey   Clé sur le modèle distant (task_id)
     * @param string|null $localKey    Clé locale du modèle actuel (id)
     * @param string|null $secondLocalKey Clé locale du modèle intermédiaire (id)
     */
    public function __construct(
        protected Elegant $model,
        protected string $farModelClass,
        protected string $throughModelClass,
        protected ?string $firstKey = null,
        protected ?string $secondKey = null,
        protected ?string $localKey = null,
        protected ?string $secondLocalKey = null
    ) {
        $through = new $this->throughModelClass;
        
        $this->firstKey  = $firstKey ?: Str::singularize($this->model->getTable()) . '_id';
        $this->secondKey = $secondKey ?: Str::singularize($through->getTable()) . '_id';
        $this->localKey  = $localKey ?: $this->model->getKey();
        $this->secondLocalKey = $secondLocalKey ?: $through->getKey();
    }

    public function get(): mixed
    {
        /** @var \Clicalmani\Database\Factory\Models\Elegant */
        $farModel = new $this->farModelClass;
        /** @var \Clicalmani\Database\Factory\Models\Elegant */
        $through  = new $this->throughModelClass;
        
        $query = $farModel->newQuery();

        // 1. Sélection des colonnes de la destination (User)
        $query->selectRaw($farModel->getTable() . '.*');

        // 2. Jointure : users.task_id = tasks.id
        $query->joinInner(
            $through->getTable(),
            "{$farModel->getTableAlias()}.{$this->secondKey}",
            "{$through->getTableAlias()}.{$this->secondLocalKey}"
        );

        // 3. Filtre : tasks.project_id = project.id
        $query->where("{$through->getTableAlias()}.{$this->firstKey} = ?", [$this->model->{$this->localKey}]);

        // 4. Retourne la collection complète
        $this->result = $farModel->fetch($this->farModelClass);

        return $this->result;
    }
}