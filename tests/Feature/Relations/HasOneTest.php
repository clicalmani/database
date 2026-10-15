<?php

declare(strict_types=1);

namespace Database\Test\Feature\Relations;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\Profile;
use Database\Test\Feature\Fixtures\Models\User;

final class HasOneTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
        $this->seedAdvancedDatabase();
    }

    public function test_has_one_returns_related_model(): void
    {
        $user = User::find(1);
        $profile = $user->profile;
        
        $this->assertInstanceOf(Profile::class, $profile);
        $this->assertSame(1, (int) $profile->user_id);
    }

    public function test_has_one_returns_null_when_no_match(): void
    {
        // User 5 n'a pas de profile
        $user = User::find(5);
        $profile = $user->profile;

        // Comportement : peut retourner null OU un modèle vide
        $this->assertTrue($profile === null || $profile->id === null);
    }

    public function test_has_one_uses_foreign_key_convention(): void
    {
        $user = User::find(2);
        $profile = $user->profile;

        $this->assertSame((int) $user->id, (int) $profile->user_id);
    }

    public function test_has_one_returns_single_result(): void
    {
        $user = User::find(1);
        $profile = $user->profile;

        $this->assertNotNull($profile->id);
    }
}