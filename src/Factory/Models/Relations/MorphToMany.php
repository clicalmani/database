<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Support\Facades\DB;
use Clicalmani\Foundation\Support\Facades\Str;
use Override;

class MorphToMany extends Relationship
{
    /**
     * @param Elegant $model        Le modèle parent (ex: Post)
     * @param string $relatedClass  Le modèle cible (ex: Comment)
     * @param string $name          Le nom de la relation (ex: 'commentable')
     * @param string $table         Le nom de la table pivot (ex: 'commentables')
     * @param string $morphKey      Clé pointant vers le parent (ex: 'commentable_id')
     * @param string $foreignKey    Clé pointant vers la cible (ex: 'comment_id')
     */
    public function __construct(
        protected Elegant $model,
        protected string $relatedClass,
        protected string $name,
        protected ?string $table = null,
        protected ?string $morphKey = null,
        protected ?string $foreignKey = null
    ) {
        $this->table = $table ?: Str::pluralize($name);
    }

    public function get(): mixed
    {
        /** @var \Clicalmani\Database\Factory\Models\Elegant */
        $related = new $this->relatedClass;
        /** @var string */
        $tablePrefix = DB::getPrefix();
        
        // Déduction des noms de colonnes
        $morphKey   = $this->morphKey   ?: $this->name . '_id';
        $morphType  = $this->name . '_type';
        $foreignKey = $this->foreignKey ?: Str::singularize($related->getTable()) . '_id';

        // Initialisation du query builder du modèle cible (ex: Comment)
        $query = $related->newQuery();
        
        // On sélectionne les données de la table cible
        $query->selectRaw($related->getTable() . '.*');
        
        // Jointure : related_table.id = pivot_table.comment_id
        $query->joinInner($this->table, $related->getKey(true), "{$tablePrefix}{$this->table}.{$foreignKey}");

        // Filtres :
        // 1. L'id du parent (ex: post_id)
        $query->where("{$tablePrefix}{$this->table}.{$morphKey} = ?", [$this->model->{$this->model->getKey()}]);
        
        // 2. Le type du parent (ex: 'App\Models\Post')
        // Important pour ne pas récupérer les commentaires d'une Vidéo qui aurait le même ID qu'un Post
        $query->where("{$tablePrefix}{$this->table}.{$morphType} = ?", [$this->model::class]);

        $this->result = $related->fetch($this->relatedClass);

        return $this->result;
    }
}