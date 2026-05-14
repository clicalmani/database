<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Collection\Collection;
use Clicalmani\Foundation\Support\Facades\DB;
use Clicalmani\Foundation\Support\Facades\Str;

class BelongsTo extends Relationship
{
    /**
     * @param Elegant $model        Le modèle enfant actuel (ex: Post)
     * @param string $parentClass   La classe du modèle parent (ex: User)
     * @param string|null $foreignKey La clé étrangère dans la table enfant (user_id)
     * @param string|null $ownerKey   La clé primaire dans la table parente (id)
     */
    public function __construct(
        protected Elegant $model,
        protected string $parentClass,
        protected ?string $foreignKey = null, 
        protected ?string $ownerKey = null
    ) {
        $parentInstance = new $parentClass;
        
        // Par défaut : user_id
        $this->foreignKey = $foreignKey ?: Str::singularize($parentInstance->getTable()) . '_id';
        
        // Par défaut : id (clé primaire du parent)
        $this->ownerKey = $ownerKey ?: $parentInstance->getKey();
    }

    /**
     * Récupère le modèle parent
     * 
     * @return mixed
     */
    public function get(): mixed
    {
        $parent = new $this->parentClass;
        
        // On récupère la valeur de la clé étrangère sur l'objet actuel
        // ex: $post->user_id
        $idToFind = $this->model->{$this->foreignKey};

        if (!$idToFind) {
            return null;
        }

        // Requête : SELECT * FROM users WHERE id = $idToFind
        $this->result = $parent->where($this->ownerKey . " = ?", [$idToFind])
                               ->first();

        return $this->result;
    }

    public function loadNestedRelations(Collection $results, $relation, $relatedclass)
    {
        $foreignKey = strtolower($relation) . '_id';
        $ids = collect($results)->pluck($foreignKey)->unique()->filter()->toArray();

        if (empty($ids)) return;
        
        /** @var \Clicalmani\Database\Factory\Models\Elegant */
        $related = new $relatedclass;
        $relatedRows = DB::table($related->getTable())->whereIn('id', $ids)->get();

        $relatedMap = [];

        foreach ($relatedRows as $row) {
            $relatedMap[$row->{$related->getKey()}] = $row;
        }

        $rows = [];

        foreach ($results as $result) {
            $relatedId = $result->$foreignKey;
            $relatedObject = $relatedMap[$relatedId] ?? null;
            $result->$relation = $relatedObject;

            $rows[] = $result;
        }

        $results->exchange($rows);
    }
}