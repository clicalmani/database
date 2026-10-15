<?php

declare(strict_types=1);

namespace Database\Test\Feature\BugTracking;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class BelongsToManyBugTest extends DatabaseTestCase
{
    /**
     * BUG #1 : BelongsToMany::attach() retourne un AbstractInsertStatement
     * au lieu d'un bool. Le code fait :
     *   $success = DB::table($this->table)->insert($insertData);
     *   // insert() retourne un objet, pas un bool
     *
     * Test : on vérifie que le retour est un bool.
     */
    public function test_bug_1_attach_returns_bool(): void
    {
        $this->seedDatabase();

        $user = User::find(4);
        $result = $user->roles()->attach(1);

        $this->assertIsBool($result, 'BUG #1 : attach() doit retourner un bool');
    }

    /**
     * BUG #1 bis : même problème sur attach() avec plusieurs IDs.
     * La dernière itération écrase $success avec un objet.
     */
    public function test_bug_1_attach_multiple_returns_bool(): void
    {
        $this->seedDatabase();

        $user = User::find(5);
        $result = $user->roles()->attach([1, 2]);

        $this->assertIsBool($result, 'BUG #1 bis : attach([1,2]) doit retourner un bool');
    }

    /**
     * BUG #2 : BelongsToMany::get() construit $this->query avec selectRaw,
     * joinInner, where, mais exécute $this->related->get() (modèle vierge).
     * → Les clauses construites sont ignorées.
     *
     * Test : on vérifie que les colonnes pivot_* sont bien présentes.
     */
    public function test_bug_2_get_includes_pivot_columns(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $roles = $user->roles()->get();

        $this->assertGreaterThan(0, $roles->count());

        $first = $roles->first();
        
        $pivot = $first->getPivot();

        $this->assertArrayHasKey(
            'user_id',
            $pivot,
            'BUG #2 : get() doit peupler le pivot avec user_id'
        );
    }

    /**
     * BUG #2 bis : vérifier que le JOIN est bien appliqué.
     * Si le bug existe, on retourne TOUS les rôles (pas seulement ceux de l'user).
     */
    public function test_bug_2_get_applies_join_and_where(): void
    {
        $this->seedDatabase();

        // User 1 a 2 rôles (admin + editor)
        $user = User::find(1);
        $roles = $user->roles()->get();

        $this->assertSame(
            2,
            $roles->count(),
            'BUG #2 bis : get() doit appliquer le WHERE sur pivot_user_id'
        );
    }

    /**
     * BUG #3 : BelongsToMany::sync() déclare $current[] sans l'initialiser.
     * Si $results est vide → warning "Undefined variable" en PHP 8.
     *
     * Test : on sync sur un user sans rôles.
     */
    public function test_bug_3_sync_with_empty_pivot(): void
    {
        $this->seedDatabase();

        // User 3 n'a aucun rôle
        $user = User::find(3);

        // Doit fonctionner sans warning
        $changes = $user->roles()->sync([1, 2]);

        $this->assertIsArray($changes);
        $this->assertCount(2, $changes['attached']);
    }

    /**
     * BUG #3 bis : même problème sur syncWithoutDetaching().
     */
    public function test_bug_3_sync_without_detaching_empty_pivot(): void
    {
        $this->seedDatabase();

        $user = User::find(3);

        $changes = $user->roles()->syncWithoutDetaching([1]);

        $this->assertIsArray($changes);
        $this->assertContains(1, $changes['attached']);
    }
}