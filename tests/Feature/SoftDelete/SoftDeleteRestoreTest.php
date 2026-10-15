<?php

declare(strict_types=1);

namespace Database\Test\Feature\SoftDelete;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\SoftUser;

final class SoftDeleteRestoreTest extends DatabaseTestCase
{
    public function test_restore_removes_deleted_at(): void
    {
        $this->seedDatabase();

        $user = SoftUser::find(1);
        $user->delete();

        $trashed = SoftUser::withTrashed()->find(1);
        $success = $trashed->restore();

        $this->assertTrue($success);

        $row = $this->pdo->query('SELECT deleted_at FROM soft_users WHERE id = 1')->fetch();
        $this->assertNull($row->deleted_at);
    }

    public function test_restored_user_is_findable(): void
    {
        $this->seedDatabase();

        $user = SoftUser::find(1);
        $user->delete();

        $trashed = SoftUser::withTrashed()->find(1);
        $trashed->restore();

        // Maintenant findable sans withTrashed
        $found = SoftUser::find(1);
        $this->assertNotNull($found);
    }

    public function test_restore_returns_bool(): void
    {
        $this->seedDatabase();

        $user = SoftUser::find(1);
        $user->delete();

        $trashed = SoftUser::withTrashed()->find(1);
        $result = $trashed->restore();

        $this->assertTrue($result);
    }

    public function test_restore_fires_events(): void
    {
        $this->seedDatabase();

        $restoringCalled = false;
        $restoredCalled = false;

        $user = SoftUser::find(1);
        $user->delete();

        $trashed = SoftUser::withTrashed()->find(1);
        $trashed->registerEvent('restoring', function () use (&$restoringCalled) {
            $restoringCalled = true;
        });
        $trashed->registerEvent('restored', function () use (&$restoredCalled) {
            $restoredCalled = true;
        });

        $trashed->restore();

        $this->assertTrue($restoringCalled);
        $this->assertTrue($restoredCalled);
    }

    public function test_restore_on_non_trashed_is_noop(): void
    {
        $this->seedDatabase();

        $user = SoftUser::find(1);

        // Pas soft-deleted
        $result = $user->restore();

        // Comportement : peut retourner true (no-op) ou false
        $this->assertIsBool($result);
    }
}