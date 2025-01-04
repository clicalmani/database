<?php
namespace Clicalmani\Database\Factory;

/**
 * Service tag to autoconfigure validators.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Index
{
    /**
     * On update cascade
     * 
     * @var int 0
     */
    const ON_UPDATE_CASCADE = 0;

    /**
     * On update restrict
     * 
     * @var int 1
     */
    const ON_UPDATE_RESTRICT = 1;

    /**
     * On update set null
     * 
     * @var int 2
     */
    const ON_UPDATE_SETNULL = 2;

    /**
     * On update no action
     * 
     * @var int 3
     */
    const ON_UPDATE_NOACTION = 3;

    /**
     * On delete cascade
     * 
     * @var int 4
     */
    const ON_DELETE_CASCADE = 4;

    /**
     * On delete restrict
     * 
     * @var int 5
     */
    const ON_DELETE_RESTRICT = 5;

    /**
     * On delete set null
     * 
     * @var int 6
     */
    const ON_DELETE_SETNULL = 6;

    /**
     * On delete no action
     * 
     * @var int 7
     */
    const ON_DELETE_NOACTION = 7;

    /**
     * Index name
     * 
     * @var string
     */
    public string $name;

    /**
     * Index key
     * 
     * @var string
     */
    public string $key;

    /**
     * Unique index
     * 
     * @var bool
     */
    public ?bool $unique = false;

    /**
     * Constraint index
     * 
     * @var ?string
     */
    public ?string $constraint = null;

    /**
     * Index references
     * 
     * @var string|array<string, string>
     */
    public string|array $references = [];

    /**
     * On update
     * 
     * @var ?int 0
     */
    public ?int $onUpdate = self::ON_UPDATE_CASCADE;

    /**
     * On update
     * 
     * @var ?int 4
     */
    public ?int $onDelete = self::ON_DELETE_CASCADE;

    public function __construct(
        string $name, 
        string $key, 
        ?bool $unique = false, 
        ?string $constraint = null,
        string|array $references = [],
        ?int $onUpdate = self::ON_UPDATE_CASCADE,
        ?int $onDelete = self::ON_DELETE_CASCADE
    )
    {
        if (is_string($references)) {
            if (is_subclass_of($references, \Clicalmani\Database\Factory\Models\Model::class)) {
                /** @var \Clicalmani\Database\Factory\Models\Model */
                $model = new $references;
                $table = $model->getTable();
                $primary_key = $model->getKey();

                if ( is_array($primary_key) ) throw new \TypeError("Expected string; array given. Reference table should not have multiple keys.");

                $references = ['table' => $table, 'key' => $primary_key];
            } else throw new \TypeError(sprintf("Expected type of %s; got %s.", \Clicalmani\Database\Factory\Models\Model::class, $references));
        }

        $this->name = $name;
        $this->key = $key;
        $this->unique = $unique;
        $this->constraint = $constraint;
        $this->references = $references;
        $this->onUpdate = $onUpdate;
        $this->onDelete = $onDelete;
    }

    public function __toString() : string
    {
        return $this->name;
    }

    public function __invoke() : array
    {
        return [
            'name' => $this->name,
            'key' => $this->key,
            'unique' => $this->unique,
            'constraint' => $this->constraint,
            'references' => $this->references,
            'onUpdate' => $this->onUpdate,
            'onDelete' => $this->onDelete
        ];
    }
}
