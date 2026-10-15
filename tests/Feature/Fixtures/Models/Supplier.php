<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures\Models;

use Clicalmani\Core\Acme\Model;
use Clicalmani\Database\Factory\Models\Relations\HasOneThrough;
use Database\Test\Feature\Fixtures\Entities\SupplierEntity;

class Supplier extends Model
{
    protected string $table = 'suppliers';
    protected string|array $primaryKey = 'id';
    protected string $entity = SupplierEntity::class;

    public function account(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, Account::class);
    }
}