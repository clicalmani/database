<?php
namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Support\Facades\DB;
use Clicalmani\Foundation\Support\Facades\Str;

class MorphedByMany extends Relationship
{
    /**
     * @param Elegant $model        Le modèle enfant (ex: Comment)
     * @param string $parentClass   La classe parente cible (ex: Post)
     * @param string $name          Le nom de la relation (ex: 'commentable')
     * @param string $table         Le nom de la table pivot (ex: 'commentables')
     * @param string $foreignKey    Clé pointant vers l'enfant (ex: 'comment_id')
     * @param string $morphKey      Clé pointant vers le parent (ex: 'commentable_id')
     */
    public function __construct(
        protected Elegant $model,
        protected string $parentClass,
        protected string $name,
        protected ?string $table = null,
        protected ?string $foreignKey = null,
        protected ?string $morphKey = null
    ) {
        $this->table = $table ?: Str::pluralize($name);
    }

    public function get(): mixed
    {
        /** @var \Clicalmani\Database\Factory\Models\Elegant */
        $parent = new $this->parentClass;
        /** @var string */
        $tablePrefix = DB::getPrefix();
        
        // Déduction des clés si null
        $foreignKey = $this->foreignKey ?: Str::singularize($this->model->getTable()) . '_id';
        $morphKey   = $this->morphKey   ?: $this->name . '_id';
        $morphType  = $this->name . '_type';

        // Construction de la requête avec jointure sur la table pivot
        $query = $parent->newQuery();
        
        // On sélectionne les colonnes du parent
        $query->selectRaw($parent->getTable() . '.*');
        
        // Jointure avec la table pivot
        $parent->join($this->table, $parent->getKey(true), "{$tablePrefix}{$this->table}.{$morphKey}");

        // Filtres : 
        // 1. Lier à l'ID du modèle actuel (l'enfant)
        // 2. Filtrer par le type morphique (la classe du parent)
        $query->where("{$tablePrefix}{$this->table}.{$foreignKey} = ?", [$this->model->{$this->model->getKey()}]);
        $query->where("{$tablePrefix}{$this->table}.{$morphType} = ?", [$this->parentClass]);

        $this->result = $parent->fetch($this->parentClass);

        return $this->result;
    }
}