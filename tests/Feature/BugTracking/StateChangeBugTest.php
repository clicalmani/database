<?php

declare(strict_types=1);

namespace Database\Test\Feature\BugTracking;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class StateChangeBugTest extends DatabaseTestCase
{
    /**
     * BUG #17 : StateChange::isDirty() fait array_merge($in, $out) sans
     * vérifier que les clés existent.
     *   return array_key_exists($attribute, array_merge($manipulated['in'], $manipulated['out']));
     *
     * → Warning "Undefined array key" en PHP 8.
     *
     * Test : sur un modèle frais (pas encore hydraté), isDirty() ne doit pas warning.
     */
    public function test_bug_17_is_dirty_on_fresh_model(): void
    {
        $user = new User;

        $warnings = [];
        set_error_handler(function ($errno, $errstr) use (&$warnings) {
            $warnings[] = $errstr;
            return true;
        }, E_WARNING);

        try {
            $user->isDirty('name');
        } finally {
            restore_error_handler();
        }

        $hasUndefinedKeyWarning = false;
        foreach ($warnings as $warning) {
            if (str_contains($warning, 'Undefined array key')) {
                $hasUndefinedKeyWarning = true;
                break;
            }
        }

        $this->assertFalse(
            $hasUndefinedKeyWarning,
            'BUG #17 : isDirty() fait array_merge sur clés absentes'
        );
    }
}