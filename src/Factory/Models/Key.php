<?php
namespace Clicalmani\Database\Factory\Models;

final class Key
{
    /** @var array<string, mixed> nom de colonne => valeur (peut être null si pas encore connue) */
    private readonly array $pairs;

    private function __construct(
        array $pairs,
        private readonly ?string $alias = null,
    ) {
        $this->pairs = $pairs;
    }

    /**
     * Construit une clé à partir du/des nom(s) de colonne(s) uniquement.
     * Utile quand on connaît la définition de la clé mais pas encore sa valeur
     * (ex: primaryKey déclarée sur le modèle).
     */
    public static function fromName(string|array $name, ?string $alias = null): self
    {
        $names = is_array($name) ? $name : [$name];
        return new self(array_fill_keys($names, null), $alias);
    }

    /**
     * Construit une clé à partir de valeur(s), en les associant à des noms.
     * Si $name est omis, les valeurs sont indexées numériquement (usage rare).
     */
    public static function fromValue(string|array $value, string|array|null $name = null): self
    {
        $values = is_array($value) ? $value : explode('|', $value);

        if ($name !== null) {
            $names = is_array($name) ? $name : [$name];
            
            if (count($names) !== count($values)) {
                throw new \InvalidArgumentException('Le nombre de noms et de valeurs de clé ne correspond pas.');
            }

            return new self(array_combine($names, $values));
        }

        return new self($values);
    }

    /**
     * Associe des valeurs à une clé qui ne portait que des noms
     * (typiquement : $model->getKey()->withValues($model->id))
     */
    public function withValues(string|array $value): self
    {
        $values = is_array($value) ? array_values($value) : [$value];
        $names  = array_keys($this->pairs);

        if (count($names) !== count($values)) {
            throw new \InvalidArgumentException('Le nombre de valeurs ne correspond pas au nombre de colonnes de la clé.');
        }

        return new self(array_combine($names, $values), $this->alias);
    }

    public function withAlias(?string $alias): self
    {
        return new self($this->pairs, $alias);
    }

    public function isComposite(): bool
    {
        return count($this->pairs) > 1;
    }

    /**
     * @return string[] Noms de colonnes, qualifiés avec l'alias si $qualified = true
     */
    public function names(bool $qualified = false): array
    {
        $names = array_keys($this->pairs);

        if (!$qualified || !$this->alias) return $names;

        return array_map(fn($n) => "{$this->alias}.{$n}", $names);
    }

    public function values(): array
    {
        return array_values($this->pairs);
    }

    public function scalarName(bool $qualified = false): string
    {
        $this->assertNotComposite('name');
        return $this->names($qualified)[0];
    }

    public function scalarValue(): mixed
    {
        $this->assertNotComposite('value');
        return array_values($this->pairs)[0];
    }

    /**
     * @return array{0: string, 1: array} [conditionSQL, bindings] prêt pour ->where($sql, $bindings)
     */
    public function toSqlCondition(string $operator = '='): array
    {
        $conditions = [];
        $bindings   = [];

        foreach ($this->pairs as $name => $value) {
            $qualified    = $this->alias ? "{$this->alias}.{$name}" : "`$name`";
            $conditions[] = "{$qualified} {$operator} ?";
            $bindings[]   = $value;
        }

        return [implode(' AND ', $conditions), $bindings];
    }

    public function toArray(): array
    {
        return $this->pairs;
    }

    /**
     * Tente de retrouver la ou les valeur(s) de cette clé dans un résultat
     * brut de requête SQL (tableau associatif colonne => valeur).
     *
     * Gère le cas où le résultat contient la colonne qualifiée (`alias.colonne`)
     * ou non-qualifiée (`colonne`), peu importe comment la clé a été construite.
     *
     * @param array $row Résultat brut d'une ligne SQL
     * @return self Nouvelle instance avec les valeurs résolues
     * @throws \OutOfBoundsException Si une colonne de la clé est introuvable dans $row
     */
    public function fromResult(array $row): self
    {
        $values = [];

        foreach (array_keys($this->pairs) as $name) {
            $value = $row[$name]                              // colonne non qualifiée
                ?? $row[$this->alias . '.' . $name]            // colonne qualifiée avec l'alias courant
                ?? $this->findByUnqualifiedMatch($row, $name); // dernier recours : "table.colonne" avec n'importe quel alias

            if ($value === null && !array_key_exists($name, $row)) {
                throw new \OutOfBoundsException(
                    sprintf("Impossible de retrouver la colonne de clé [%s] dans le résultat fourni.", $name)
                );
            }

            $values[$name] = $value;
        }

        return new self($values, $this->alias);
    }

    /**
     * Cherche une colonne "quelquechose.$name" dans $row, peu importe le préfixe,
     * pour couvrir le cas où l'alias réel diffère de celui connu par cette instance
     * (ex: jointure avec un alias différent).
     */
    private function findByUnqualifiedMatch(array $row, string $name): mixed
    {
        foreach ($row as $column => $value) {
            if (str_ends_with($column, ".{$name}")) {
                return $value;
            }
        }

        return null;
    }

    private function assertNotComposite(string $what): void
    {
        if ($this->isComposite()) {
            throw new \LogicException("Impossible de réduire une clé composite à un(e) seul(e) {$what}.");
        }
    }

    public function __toString(): string
    {
        return $this->isComposite()
            ? implode('|', $this->values())
            : (string) $this->scalarValue();
    }
}