<?php

declare(strict_types=1);

namespace Database\Test\Feature\BugTracking;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class SQLClausesBugTest extends DatabaseTestCase
{
    /**
     * BUG #15 : SQLClauses::orWhereHas() utilise $boolean non défini.
     *   public function orWhereHas(string $relation, \Closure $callback) : self {
     *       return $this->query->whereHas(instance($relation)->getTable()->name(), $callback, $boolean);
     *       //                                                                              ^^^^^^^^
     *   }
     *
     * → Warning "Undefined variable $boolean" + logique incorrecte
     *   (devrait appeler orWhereHas, pas whereHas).
     *
     * Test : on appelle orWhereHas() sur un modèle.
     */
    public function test_bug_15_or_where_has_does_not_use_undefined_variable(): void
    {
        $this->seedDatabase();

        $user = new User;

        $warnings = [];
        set_error_handler(function ($errno, $errstr) use (&$warnings) {
            $warnings[] = $errstr;
            return true;
        }, E_WARNING);

        try {
            $user->orWhereHas('posts', function ($q) {
                // Callback
            });
        } catch (\Throwable $e) {
            // On note l'exception mais on continue
        } finally {
            restore_error_handler();
        }

        $hasUndefinedVarWarning = false;
        foreach ($warnings as $warning) {
            if (str_contains($warning, 'Undefined variable') && str_contains($warning, 'boolean')) {
                $hasUndefinedVarWarning = true;
                break;
            }
        }

        $this->assertFalse(
            $hasUndefinedVarWarning,
            'BUG #15 : orWhereHas() utilise $boolean non défini'
        );
    }
}