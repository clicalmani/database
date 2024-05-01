<?php
namespace Clicalmani\Database\Factory;

/**
 * Service tag to autoconfigure validators.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
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
     * @var ?array<string, string>
     */
    public ?array $references = [];

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
        ?array $references = [],
        ?int $onUpdate = self::ON_UPDATE_CASCADE,
        ?int $onDelete = self::ON_DELETE_CASCADE
    )
    {
        $this->name = $name;
        $this->key = $key;
        $this->unique = $unique;
        $this->constraint = $constraint;
        $this->references = $references;
        $this->onUpdate = $onUpdate;
        $this->onDelete = $onDelete;
    }
}
