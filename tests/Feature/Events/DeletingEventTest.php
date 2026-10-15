<?php

declare(strict_types=1);

namespace Database\Test\Feature\Events;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class DeletingEventTest extends DatabaseTestCase
{
    public function test_deleting_is_called_before_delete(): void
    {
        $this->seedDatabase();

        $called = false;

        $user = User::find(1);
        $user->registerEvent('deleting', function () use (&$called) {
            $called = true;
        });

        $user->delete();

        $this->assertTrue($called);
    }

    public function test_deleting_can_prevent_delete(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $user->registerEvent('deleting', function (User $u) {
            throw new \RuntimeException('Cannot delete');
        });

        try {
            $user->delete();
            $this->fail('delete() aurait dû throw');
        } catch (\RuntimeException $e) {
            // Attendu
        }

        // Vérifier que la ligne est toujours là
        $this->assertNotNull(User::find(1));
    }

    public function test_deleted_is_called_after_delete(): void
    {
        $this->seedDatabase();

        $called = false;

        $user = User::find(1);
        $user->registerEvent('deleted', function () use (&$called) {
            $called = true;
        });

        $user->delete();

        $this->assertTrue($called);
    }

    public function test_deleting_and_deleted_are_in_order(): void
    {
        $this->seedDatabase();

        $order = [];

        $user = User::find(1);
        $user->registerEvent('deleting', function () use (&$order) { $order[] = 'deleting'; });
        $user->registerEvent('deleted',  function () use (&$order) { $order[] = 'deleted'; });

        $user->delete();

        $this->assertSame(['deleting', 'deleted'], $order);
    }
}