<?php

declare(strict_types=1);

namespace Database\Test\Feature\Events;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class SavingEventTest extends DatabaseTestCase
{
    public function test_saving_is_called_on_insert(): void
    {
        $called = false;

        $user = new User;
        $user->name  = 'Insert';
        $user->email = 'insert@example.com';
        $user->age   = 20;

        $user->registerEvent('saving', function () use (&$called) {
            $called = true;
        });

        $user->save();

        $this->assertTrue($called);
    }

    public function test_saving_is_called_on_update(): void
    {
        $this->seedDatabase();

        $called = false;

        $user = User::find(1);
        $user->name = 'Update';
        $user->registerEvent('saving', function () use (&$called) {
            $called = true;
        });

        $user->save();

        $this->assertTrue($called, 'saving doit être appelé sur update');
    }

    public function test_saved_is_called_on_insert(): void
    {
        $called = false;

        $user = new User;
        $user->name  = 'SavedInsert';
        $user->email = 'savedinsert@example.com';
        $user->age   = 20;

        $user->registerEvent('saved', function () use (&$called) {
            $called = true;
        });

        $user->save();

        $this->assertTrue($called);
    }

    public function test_saved_is_called_on_update(): void
    {
        $this->seedDatabase();

        $called = false;

        $user = User::find(1);
        $user->name = 'SavedUpdate';
        $user->registerEvent('saved', function () use (&$called) {
            $called = true;
        });

        $user->save();

        $this->assertTrue($called);
    }

    public function test_save_quietly_mutes_events(): void
    {
        $savingCalled = false;
        $savedCalled = false;

        $user = new User;
        $user->name  = 'Quiet';
        $user->email = 'quiet@example.com';
        $user->age   = 20;

        $user->registerEvent('saving', function () use (&$savingCalled) { $savingCalled = true; });
        $user->registerEvent('saved',  function () use (&$savedCalled)  { $savedCalled = true; });

        $user->saveQuietly();

        $this->assertFalse($savingCalled, 'saveQuietly() doit muter saving');
        $this->assertFalse($savedCalled,  'saveQuietly() doit muter saved');
    }

    public function test_saving_and_saved_are_called_in_order(): void
    {
        $order = [];

        $user = new User;
        $user->name  = 'OrderTest';
        $user->email = 'ordertest@example.com';
        $user->age   = 20;

        $user->registerEvent('saving', function () use (&$order) { $order[] = 'saving'; });
        $user->registerEvent('saved',  function () use (&$order) { $order[] = 'saved'; });

        $user->save();

        $this->assertSame(['saving', 'saved'], $order);
    }
}