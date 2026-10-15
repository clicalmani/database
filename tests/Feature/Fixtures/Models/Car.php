<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures\Models;

use Clicalmani\Core\Acme\Model;
use Clicalmani\Database\Factory\Models\Relations\BelongsTo;
use Database\Test\Feature\Fixtures\Entities\CarEntity;

class Car extends Model
{
    protected string $table = 'cars';
    protected string|array $primaryKey = 'id';
    protected string $entity = CarEntity::class;

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(Mechanic::class);
    }
}