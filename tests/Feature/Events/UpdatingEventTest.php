<?php

declare(strict_types=1);

namespace Database\Test\Feature\Events;

use PDO;
use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class UpdatingEventTest extends DatabaseTestCase
{
    public function test_updating_is_called_before_update(): void
    {
        $this->seedDatabase();

        $called = false;

        $user = User::find(1);
        $user->name = 'NewName';
        $user->registerEvent('updating', function () use (&$called) {
            $called = true;
        });

        $user->save();

        $this->assertTrue($called);
    }

    public function test_updating_can_modify_values(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $user->name = 'Original';

        $user->registerEvent('updating', function (User $u) {
            $u->name = 'Modified by event';
        });

        $user->save();

        $row = $this->pdo->query('SELECT name FROM users WHERE id = 1')->fetch(PDO::FETCH_OBJ);
        $this->assertSame('Modified by event', $row->name);
    }

    public function test_updated_is_called_after_update(): void
    {
        $this->seedDatabase();

        $called = false;

        $user = User::find(1);
        $user->name = 'Updated';
        $user->registerEvent('updated', function () use (&$called) {
            $called = true;
        });

        $user->save();

        $this->assertTrue($called);
    }

    public function test_updating_is_not_called_on_insert(): void
    {
        $called = false;

        $user = new User;
        $user->name  = 'Insert';
        $user->email = 'insertevent@example.com';
        $user->age   = 20;

        $user->registerEvent('updating', function () use (&$called) {
            $called = true;
        });

        $user->save();

        $this->assertFalse($called, 'updating ne doit pas être appelé sur insert');
    }

    public function test_update_via_method_fires_events(): void
    {
        $this->seedDatabase();

        $called = false;

        $user = User::find(1);
        $user->registerEvent('updating', function () use (&$called) {
            $called = true;
        });

        $user->update(['name' => 'MethodUpdate']);

        $this->assertTrue($called, 'update() doit déclencher updating');
    }
}