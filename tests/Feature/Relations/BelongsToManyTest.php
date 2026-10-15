<?php

declare(strict_types=1);

namespace Database\Test\Feature\Relations;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class BelongsToManyTest extends DatabaseTestCase
{
    public function test_belongs_to_many_returns_collection(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $roles = $user->roles;

        $this->assertGreaterThan(0, count($roles));
    }

    public function test_attach_creates_pivot_entry(): void
    {
        $this->seedDatabase();

        $user = User::find(4);

        $result = $user->roles()->attach(1);

        $this->assertTrue($result);
        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM role_user WHERE user_id = 4 AND role_id = 1')->fetchColumn();
        $this->assertSame(1, $count);
    }

    public function test_detach_removes_pivot_entry(): void
    {
        $this->seedDatabase();

        $user = User::find(1);

        $result = $user->roles()->detach(1);

        $this->assertTrue($result);
        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM role_user WHERE user_id = 1 AND role_id = 1')->fetchColumn();
        $this->assertSame(0, $count);
    }
}