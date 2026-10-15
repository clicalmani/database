<?php

declare(strict_types=1);

namespace Database\Test\Feature\SoftDelete;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\SoftUser;

final class SoftDeleteQueryTest extends DatabaseTestCase
{
    // ─── DEFAULT SCOPE ─────────────────────────────────────

    public function test_get_excludes_soft_deleted(): void
    {
        $this->seedDatabase();

        SoftUser::find(1)->delete();
        SoftUser::find(2)->delete();

        $users = SoftUser::all();

        $this->assertSame(3, $users->count());
    }

    public function test_find_returns_null_for_soft_deleted(): void
    {
        $this->seedDatabase();

        SoftUser::find(1)->delete();

        $this->assertNull(SoftUser::find(1));
    }

    public function test_first_excludes_soft_deleted(): void
    {
        $this->seedDatabase();

        SoftUser::find(1)->delete();

        $first = SoftUser::orderBy('id', 'ASC')->first();

        // Doit retourner SoftUser 2 (car SoftUser 1 est soft-deleted)
        $this->assertSame(2, (int) $first->id);
    }

    public function test_count_excludes_soft_deleted(): void
    {
        $this->seedDatabase();

        SoftUser::find(1)->delete();

        $this->assertSame(4, SoftUser::count());
    }

    // ─── WITH TRASHED ──────────────────────────────────────

    public function test_with_trashed_includes_soft_deleted(): void
    {
        $this->seedDatabase();

        SoftUser::find(1)->delete();
        SoftUser::find(2)->delete();

        $users = SoftUser::withTrashed()->get();

        $this->assertSame(5, $users->count());
    }

    public function test_with_trashed_find_returns_trashed(): void
    {
        $this->seedDatabase();

        SoftUser::find(1)->delete();

        $found = SoftUser::withTrashed()->find(1);

        $this->assertNotNull($found);
        $this->assertTrue($found->trashed());
    }

    public function test_with_trashed_count_includes_all(): void
    {
        $this->seedDatabase();

        SoftUser::find(1)->delete();
        SoftUser::find(2)->delete();
        SoftUser::find(3)->delete();

        $count = SoftUser::withTrashed()->count();

        $this->assertSame(5, $count);
    }

    // ─── ONLY TRASHED ──────────────────────────────────────

    public function test_only_trashed_returns_only_soft_deleted(): void
    {
        $this->seedDatabase();

        SoftUser::find(1)->delete();
        SoftUser::find(3)->delete();

        $trashed = SoftUser::onlyTrashed()->get();

        $this->assertSame(2, $trashed->count());
    }

    public function test_only_trashed_returns_empty_when_none(): void
    {
        $this->seedDatabase();

        $trashed = SoftUser::onlyTrashed()->get();

        $this->assertSame(0, $trashed->count());
    }

    public function test_only_trashed_find(): void
    {
        $this->seedDatabase();

        SoftUser::find(2)->delete();

        $found = SoftUser::onlyTrashed()->find(2);

        $this->assertNotNull($found);
        $this->assertSame(2, (int) $found->id);
    }

    // ─── WITHOUT TRASHED ───────────────────────────────────

    public function test_without_trashed_is_default(): void
    {
        $this->seedDatabase();

        SoftUser::find(1)->delete();

        $users = SoftUser::withoutTrashed()->get();

        $this->assertSame(4, $users->count());
    }

    public function test_switch_from_with_to_without(): void
    {
        $this->seedDatabase();

        SoftUser::find(1)->delete();

        $with = SoftUser::withTrashed()->get();
        $without = SoftUser::withoutTrashed()->get();

        $this->assertSame(5, $with->count());
        $this->assertSame(4, $without->count());
    }
}