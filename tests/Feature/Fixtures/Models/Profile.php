<?php

declare(strict_types=1);

namespace Database\Test\Feature\Fixtures\Models;

use Clicalmani\Core\Acme\Model;
use Clicalmani\Database\Factory\Models\Relations\BelongsTo;
use Database\Test\Feature\Fixtures\Entities\ProfileEntity;

class Profile extends Model
{
    protected string $table = 'profiles';
    protected string|array $primaryKey = 'id';
    protected string $entity = ProfileEntity::class;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}