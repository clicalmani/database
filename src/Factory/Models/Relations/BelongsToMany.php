<?php

namespace Clicalmani\Database\Factory\Models\Relations;

use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Database\Interfaces\JoinClauseInterface;
use Clicalmani\Foundation\Support\Facades\Str;

class BelongsToMany extends Relationship
{
    protected Elegant $parent; // Modèle depuis lequel la relation est définie (ex: User)
    protected Elegant $related;

    private string $callerClass = '';

    public function __construct(
        protected string $relatedClass, 
        protected ?string $pivotTable = null,
        protected ?string $foreignPivotKey = null, // ex: user_id (pointe vers le parent)
        protected ?string $relatedPivotKey = null, // ex: role_id (pointe vers le related)
        protected ?string $parentKey = null,       // ex: users.id
        protected ?string $relatedKey = null,      // ex: roles.id
        protected ?string $parentClass = null
    ) {
        $this->parent = new $parentClass;
        $this->related = new $relatedClass;
        
        // Convention de nommage pour la table pivot : nom_des_tables_au_singulier_alphabétiquement (ex: role_user)
        $this->pivotTable = $pivotTable ?? $this->getDefaultPivotTable();
        
        // Convention de nommage pour les clés
        $this->foreignPivotKey = $foreignPivotKey ?? Str::singularize($this->parent->getTable()) . '_id';
        $this->relatedPivotKey = $relatedPivotKey ?? Str::singularize($this->related->getTable()) . '_id';
        $this->parentKey = $parentKey ?? $this->parent->getKey();
        $this->relatedKey = $relatedKey ?? $this->related->getKey();

        $this->callerClass = $this->getCallerClassFromNew();
    }

    protected function getParentClass(): string
    {
        return $this->parent::class;
    }

    /**
     * Génère le nom par défaut de la table pivot (ex: role_user au lieu de user_role)
     */
    private function getDefaultPivotTable(): string
    {
        $tables = [
            Str::singularize($this->parent->getTable()),
            Str::singularize($this->related->getTable())
        ];
        sort($tables); // Trie alphabétique
        return implode('_', $tables);
    }

    public function get(?string $id = null): array
    {
        // Si l'appelant est le parent (ex: User), on requête le modèle lié (ex: Role). Sinon, on requête le parent.
        $query = ($this->callerClass === $this->parentClass) ? $this->related->getQuery() : $this->parent->getQuery();
        
        // Plus de condition sur le "_type" ici !
        $query->where($this->getWhereCondition(), [$id]);
        
        $query->join($this->pivotTable, fn(JoinClauseInterface $join) => 
            $join->inner()->on($this->getJoinCondition()));

        return ($this->callerClass === $this->parentClass) ? $this->related->fetch()->toArray(): $this->parent->fetch()->toArray();
    }

    private function getTablePrefix(): string
    {
        return $this->parent->getQuery()->getPrefix();
    }

    private function getWhereCondition()
    {
        // Si appelé par le parent, on filtre sur la clé du parent dans le pivot. Sinon sur la clé du related.
        return ($this->callerClass === $this->parentClass) 
            ? "{$this->getTablePrefix()}{$this->pivotTable}.{$this->foreignPivotKey} = ?"
            : "{$this->getTablePrefix()}{$this->pivotTable}.{$this->relatedPivotKey} = ?";
    }

    private function getJoinCondition()
    {
        // Si appelé par le parent, on joint sur la clé du related. Sinon sur la clé du parent.
        return ($this->callerClass === $this->parentClass) 
            ? "{$this->getTablePrefix()}{$this->pivotTable}.{$this->relatedPivotKey} = {$this->related->getTableAlias()}.{$this->relatedKey}" 
            : "{$this->getTablePrefix()}{$this->pivotTable}.{$this->foreignPivotKey} = {$this->parent->getTableAlias()}.{$this->parentKey}";
    }
}