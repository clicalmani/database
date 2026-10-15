<?php

declare(strict_types=1);

namespace Database\Test\Feature\EdgeCases;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class HydrationEdgeTest extends DatabaseTestCase
{
    public function test_hydration_with_extra_columns(): void
    {
        $this->seedDatabase();

        // SELECT avec colonne calculée non mappée
        $users = User::selectRaw('users.*, LENGTH(name) AS name_length')->get();

        $this->assertGreaterThan(0, $users->count());

        $first = $users->first();
        $this->assertNotNull($first->id);
    }

    public function test_hydration_with_missing_columns(): void
    {
        $this->seedDatabase();

        // SELECT partiel : on n'inclut pas 'email'
        $users = \Clicalmani\Core\Support\Facades\DB::table('users')
            ->get('id, name');

        $first = $users->first();
        $this->assertNotNull($first->id);
        $this->assertNotNull($first->name);
    }

    public function test_get_original_returns_hydrated_values(): void
    {
        $this->seedDatabase();

        $user = User::find(1);

        // getOriginal() doit retourner les valeurs d'origine
        $original = $user->getOriginal();

        $this->assertNotNull($original);
    }

    public function test_rehydratation_after_update(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $user->name = 'Updated';
        $user->save();

        $fresh = User::find(1);
        $this->assertSame('Updated', $fresh->name);
    }

    public function test_hydration_after_join(): void
    {
        $this->seedDatabase();

        // Requête avec JOIN : users + posts
        $results = \Clicalmani\Core\Support\Facades\DB::table('users')
            ->joinInner('posts', 'posts.user_id', 'users.id')
            ->get();

        $this->assertGreaterThan(0, $results->count());
    }

    public function test_ambiguous_column_in_join(): void
    {
        $this->seedDatabase();

        // users.id et posts.id existent tous les deux
        // Le SELECT doit qualifier explicitement
        $results = \Clicalmani\Core\Support\Facades\DB::table('users')
            ->selectRaw('users.id AS user_id, posts.id AS post_id')
            ->joinInner('posts', 'posts.user_id', 'users.id')
            ->get();

        $first = $results->first();
        $this->assertNotNull($first->user_id);
        $this->assertNotNull($first->post_id);
    }
}