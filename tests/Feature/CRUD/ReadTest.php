<?php

declare(strict_types=1);

namespace Tests\Feature\CRUD;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class ReadTest extends DatabaseTestCase
{
    // ─── FIND ──────────────────────────────────────────────

    public function test_find_returns_model_with_all_attributes(): void
    {
        $this->seedDatabase();

        $user = User::find(1);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(1, (int) $user->id);
        $this->assertSame('User 1', $user->name);
        $this->assertSame('user1@example.com', $user->email);
        $this->assertSame(21, (int) $user->age);
    }

    public function test_find_casts_attributes(): void
    {
        $this->seedDatabase();

        $user = User::find(1);

        // age et active sont des int, pas des string
        $this->assertIsInt($user->age);
        $this->assertIsInt($user->active);
    }

    public function test_find_returns_null_for_missing_id(): void
    {
        $this->seedDatabase();

        $this->assertNull(User::find(9999));
    }

    // ─── WHERE / GET ───────────────────────────────────────

    public function test_where_returns_collection(): void
    {
        $this->seedDatabase();

        $users = User::where('active = ?', [1])->get();

        $this->assertCount(3, $users);
        $this->assertInstanceOf(User::class, $users->first());
    }

    public function test_first_returns_single_model(): void
    {
        $this->seedDatabase();

        $user = User::where('id = ?', [3])->first();

        $this->assertSame('User 3', $user->name);
    }

    public function test_all_returns_every_row(): void
    {
        $this->seedDatabase();

        $users = User::all();

        $this->assertSame(5, $users->count());
    }

    // ─── ORDER / LIMIT ─────────────────────────────────────

    public function test_order_by_desc(): void
    {
        $this->seedDatabase();

        $users = User::orderBy('id', 'DESC')->get();
        $ids = array_map(fn($u) => (int) $u->id, $users->toArray());

        $this->assertSame([5, 4, 3, 2, 1], $ids);
    }

    public function test_limit(): void
    {
        $this->seedDatabase();

        $users = User::limit(2)->get();

        $this->assertSame(2, $users->count());
    }

    // ─── VALUE / FIRSTVALUE ────────────────────────────────

    public function test_value_returns_scalar(): void
    {
        $this->seedDatabase();

        $name = User::where('id = ?', [2])->value('name');

        $this->assertSame('User 2', $name);
    }

    public function test_first_value_returns_null_for_no_match(): void
    {
        $this->seedDatabase();

        $value = User::where('id = ?', [999])->firstValue('name');

        $this->assertNull($value);
    }

    // ─── HYDRATATION ───────────────────────────────────────

    public function test_hydrated_model_has_original_data(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $original = $user->getOriginal();

        // Selon l'implémentation de getOriginal()
        $this->assertNotNull($original);
    }

    public function test_hydrated_model_is_clean(): void
    {
        $this->seedDatabase();

        $user = User::find(1);

        $this->assertFalse($user->isDirty('name'));
    }
}