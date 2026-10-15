<?php

declare(strict_types=1);

namespace Database\Test\Feature\Events;

use PDO;
use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class CreatingEventTest extends DatabaseTestCase
{
    public function test_creating_is_called_before_insert(): void
    {
        $called = false;

        $user = new User;
        $user->name  = 'Alice';
        $user->email = 'alice@example.com';
        $user->age   = 30;

        $user->registerEvent('creating', function (User $u) use (&$called) {
            $called = true;
        });

        $user->save();

        $this->assertTrue($called, 'creating doit être appelé avant save()');
    }

    public function test_creating_can_modify_attributes(): void
    {
        $user = new User;
        $user->name  = 'Bob';
        $user->email = 'bob@example.com';
        $user->age   = 25;

        $user->registerEvent('creating', function (User $u) {
            $u->name = 'Bob Modified';
        });

        $user->save();

        $row = $this->pdo->query("SELECT name FROM users WHERE email = 'bob@example.com'")->fetch(PDO::FETCH_OBJ);
        $this->assertSame('Bob Modified', $row->name);
    }

    public function test_created_is_called_after_insert(): void
    {
        $called = false;
        $capturedId = null;

        $user = new User;
        $user->name  = 'Charlie';
        $user->email = 'charlie@example.com';
        $user->age   = 35;

        $user->registerEvent('created', function (User $u) use (&$called, &$capturedId) {
            $called = true;
            $capturedId = $u->id;
        });

        $user->save();

        $this->assertTrue($called, 'created doit être appelé après save()');
        $this->assertNotNull($capturedId, 'created reçoit le modèle avec ID peuplé');
    }

    public function test_creating_and_created_are_called_in_order(): void
    {
        $order = [];

        $user = new User;
        $user->name  = 'Order';
        $user->email = 'order@example.com';
        $user->age   = 20;

        $user->registerEvent('creating', function () use (&$order) { $order[] = 'creating'; });
        $user->registerEvent('created',  function () use (&$order) { $order[] = 'created'; });

        $user->save();

        $this->assertSame(['creating', 'created'], $order);
    }

    public function test_creating_is_not_called_on_update(): void
    {
        $this->seedDatabase();

        $called = false;

        $user = User::find(1);
        $user->name = 'Updated';

        $user->registerEvent('creating', function () use (&$called) {
            $called = true;
        });

        $user->save();

        $this->assertFalse($called, 'creating ne doit pas être appelé sur update()');
    }
}