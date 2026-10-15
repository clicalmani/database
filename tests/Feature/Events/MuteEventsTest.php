<?php

declare(strict_types=1);

namespace Database\Test\Feature\Events;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class MuteEventsTest extends DatabaseTestCase
{
    public function test_mute_events_disables_all_events(): void
    {
        $called = false;

        $user = new User;
        $user->name  = 'Mute';
        $user->email = 'mute@example.com';
        $user->age   = 20;

        $user->registerEvent('creating', function () use (&$called) {
            $called = true;
        });

        $user->muteEvents();
        $user->save();

        $this->assertFalse($called, 'muteEvents() doit désactiver creating');
    }

    public function test_mute_events_with_specific_names(): void
    {
        $creatingCalled = false;
        $savingCalled = false;

        $user = new User;
        $user->name  = 'MuteSpec';
        $user->email = 'mutespec@example.com';
        $user->age   = 20;

        $user->registerEvent('creating', function () use (&$creatingCalled) { $creatingCalled = true; });
        $user->registerEvent('saving',   function () use (&$savingCalled)   { $savingCalled = true; });

        $user->muteEvents(['creating']);
        $user->save();

        $this->assertFalse($creatingCalled, 'creating doit être muté');
        $this->assertTrue($savingCalled, 'saving ne doit pas être muté');
    }

    public function test_is_event_muted_returns_correct_status(): void
    {
        $user = new User;
        $user->muteEvents(['creating']);

        $this->assertTrue($user->isEventMuted('creating'));
        $this->assertFalse($user->isEventMuted('saving'));
    }

    public function test_mute_all_events_returns_true_for_any_event(): void
    {
        $user = new User;
        $user->muteEvents();

        $this->assertTrue($user->isEventMuted('creating'));
        $this->assertTrue($user->isEventMuted('saving'));
        $this->assertTrue($user->isEventMuted('deleting'));
    }
}