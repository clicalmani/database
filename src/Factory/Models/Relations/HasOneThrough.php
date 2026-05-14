<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Support\Facades\Str;

class HasOneThrough extends Relationship
{
    /**
     * @param Elegant $model           Le modèle actuel (ex: User)
     * @param string $farModelClass    Le modèle cible distant (ex: Log)
     * @param string $throughModelClass Le modèle intermédiaire (ex: Profile)
     * @param string|null $firstKey    Clé étrangère sur le modèle intermédiaire (user_id)
     * @param string|null $secondKey   Clé étrangère sur le modèle distant (profile_id)
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

        // 1. Sélectionner les colonnes du modèle distant
        $query->selectRaw($farModel->getTable() . '.*');

        // 2. Joindre le modèle intermédiaire : logs.profile_id = profiles.id
        $query->joinInner(
            $through->getTable(),
            "{$farModel->getTableAlias()}.{$this->secondKey}",
            "{$through->getTableAlias()}.{$this->secondLocalKey}"
        );

        // 3. Filtrer par l'ID du modèle actuel : profiles.user_id = user.id
        $query->where("{$through->getTableAlias()}.{$this->firstKey} = ?", [$this->model->{$this->localKey}]);

        // 4. On ne veut qu'un seul résultat (HasONE)
        $this->result = $farModel->fetchOne($this->farModelClass);

        return $this->result;
    }
}