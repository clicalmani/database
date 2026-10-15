<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures\Models;

use Clicalmani\Core\Acme\Model;
use Clicalmani\Database\Factory\Models\Relations\BelongsTo;
use Database\Test\Feature\Fixtures\Entities\AccountEntity;

class Account extends Model
{
    protected string $table = 'accounts';
    protected string|array $primaryKey = 'id';
    protected string $entity = AccountEntity::class;

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}