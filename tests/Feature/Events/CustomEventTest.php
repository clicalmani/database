<?php

declare(strict_types=1);

namespace Database\Test\Feature\Events;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class CustomEventTest extends DatabaseTestCase
{
    public function test_register_custom_event(): void
    {
        $user = new User;

        // isEvent() doit accepter les events built-in
        $this->assertTrue($user->isEvent('creating'));
        $this->assertTrue($user->isEvent('saving'));
        $this->assertTrue($user->isEvent('deleting'));
    }

    public function test_is_event_returns_false_for_unknown(): void
    {
        $user = new User;

        $this->assertFalse($user->isEvent('unknown_event'));
    }

    public function test_emit_throws_for_unknown_event(): void
    {
        $user = new User;

        $this->expectException(\RuntimeException::class);

        $user->emit('not_registered');
    }

    public function test_prevent_events_capturing_globally(): void
    {
        User::preventEventsCapturing();

        $this->assertTrue(User::isEventsCapturingPrevented());

        User::allowEventsCapturing();

        $this->assertFalse(User::isEventsCapturingPrevented());
    }

    public function test_register_event_ignored_when_capturing_prevented(): void
    {
        User::preventEventsCapturing();

        $called = false;
        $user = new User;

        $user->registerEvent('creating', function () use (&$called) {
            $called = true;
        });

        $user->name  = 'Prevented';
        $user->email = 'prevented@example.com';
        $user->age   = 20;
        $user->save();

        User::allowEventsCapturing();

        $this->assertFalse($called, 'registerEvent() doit être ignoré si capturing prevent');
    }
}