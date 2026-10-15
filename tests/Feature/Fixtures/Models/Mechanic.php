<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures\Models;

use Clicalmani\Core\Acme\Model;
use Clicalmani\Database\Factory\Models\Relations\HasOne;
use Database\Test\Feature\Fixtures\Entities\MechanicEntity;

class Mechanic extends Model
{
    protected string $table = 'mechanics';
    protected string|array $primaryKey = 'id';
    protected string $entity = MechanicEntity::class;

    public function car(): HasOne
    {
        return $this->hasOne(Car::class);
    }
}