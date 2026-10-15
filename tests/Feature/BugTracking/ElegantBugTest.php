<?php

declare(strict_types=1);

namespace Database\Test\Feature\BugTracking;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class ElegantBugTest extends DatabaseTestCase
{
    /**
     * BUG #8 : Elegant::pivot() utilise `count($table)` au lieu de `count($tables)`.
     *   $tables = explode('_', $pivotModel->getTable()->name());
     *   if ( count($table) !== 2 ) {  // ← $table n'existe pas !
     *       return $this;
     *   }
     *
     * → Warning "Undefined variable $table" en PHP 8.
     *
     * Test : on appelle pivot() avec une table qui a un underscore (role_user).
     */
    public function test_bug_8_pivot_does_not_use_undefined_variable(): void
    {
        $this->seedDatabase();

        $user = User::find(1);

        // Capture les warnings
        $warnings = [];
        set_error_handler(function ($errno, $errstr) use (&$warnings) {
            $warnings[] = $errstr;
            return true;
        }, E_WARNING);

        try {
            // La méthode privée pivot() est appelée en interne par pivotLeft/pivotRight
            // On va utiliser la réflexion pour l'appeler directement
            $method = new \ReflectionMethod($user, 'pivot');
            $method->setAccessible(true);

            // Appel avec un pivotModel qui a un underscore dans le nom
            $method->invoke($user, \Database\Test\Feature\Fixtures\Models\Role::class);
        } catch (\Throwable $e) {
            // Ignore les exceptions
        } finally {
            restore_error_handler();
        }

        $hasUndefinedVarWarning = false;
        foreach ($warnings as $warning) {
            if (str_contains($warning, 'Undefined variable')) {
                $hasUndefinedVarWarning = true;
                break;
            }
        }

        $this->assertFalse(
            $hasUndefinedVarWarning,
            'BUG #8 : pivot() utilise $table non défini au lieu de $tables'
        );
    }
}