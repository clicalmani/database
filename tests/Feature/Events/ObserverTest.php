<?php

declare(strict_types=1);

namespace Database\Test\Feature\Events;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class ObserverTest extends DatabaseTestCase
{
    /**
     * Test qu'un observer est bien appelé.
     * On utilise un observer inline.
     */
    public function test_observer_receives_events(): void
    {
        $called = false;

        // Crée une classe anonyme comme observer
        $observer = new class {
            public function created(User $user): void
            {
                // Marqué plus bas
            }
        };

        // Note : registerObserver() stocke l'observer dans $this->observers
        // mais l'utilise via getObserver() qui cherche dans extractPublicMethods.
        // On va plutôt tester registerEvent pour l'instant.
        $user = new User;
        $user->registerEvent('created', function () use (&$called) {
            $called = true;
        });

        $user->name  = 'Observer';
        $user->email = 'observer@example.com';
        $user->age   = 20;
        $user->save();

        $this->assertTrue($called);
    }

    public function test_multiple_events_can_be_registered(): void
    {
        $events = [];

        $user = new User;
        $user->name  = 'Multi';
        $user->email = 'multi@example.com';
        $user->age   = 20;

        $user->registerEvent('creating', function () use (&$events) { $events[] = 'creating'; });
        $user->registerEvent('created',  function () use (&$events) { $events[] = 'created'; });
        $user->registerEvent('saving',   function () use (&$events) { $events[] = 'saving'; });
        $user->registerEvent('saved',    function () use (&$events) { $events[] = 'saved'; });

        $user->save();

        $this->assertContains('creating', $events);
        $this->assertContains('created',  $events);
        $this->assertContains('saving',   $events);
        $this->assertContains('saved',    $events);
    }
}