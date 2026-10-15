<?php

declare(strict_types=1);

namespace Database\Test\Feature;

use Clicalmani\Core\Support\Facades\DB;
use Clicalmani\Database\DBQuery;

final class DBQueryTest extends DatabaseTestCase
{
    private function users(): DBQuery
    {
        return DB::table('users');
    }

    // ─── WHERE ─────────────────────────────────────────────

    public function test_where_equals(): void
    {
        $this->seedDatabase();
        
        $result = $this->users()->where('active = ?', [1])->get();

        $this->assertCount(3, $result);  // users 1, 3, 5 → active = 1%2=1, 0%2=0... en fait 1 et 3 et 5
    }

    public function test_where_with_multiple_params(): void
    {
        $this->seedDatabase();

        $result = $this->users()->where('age > ? AND age < ?', [21, 24])->get();

        $this->assertGreaterThan(0, $result->count());
    }

    public function test_or_where(): void
    {
        $this->seedDatabase();

        $result = $this->users()
            ->where('id = ?', [1])
            ->orWhere('id = ?', [2])
            ->get();

        $this->assertCount(2, $result);
    }

    public function test_where_in(): void
    {
        $this->seedDatabase();

        $result = $this->users()->whereIn('id', [1, 2, 3])->get();

        $this->assertCount(3, $result);
    }

    public function test_where_in_with_empty_array_returns_all(): void
    {
        $this->seedDatabase();

        // ⚠️ Cas limite : whereIn avec tableau vide
        $result = $this->users()->whereIn('id', [])->get();

        // Doit retourner tous (car "1 = 1") selon l'implémentation actuelle
        $this->assertSame(5, $result->count());
    }

    public function test_where_not_in(): void
    {
        $this->seedDatabase();

        $result = $this->users()->whereNotIn('id', [1, 2])->get();

        $this->assertCount(3, $result);
    }

    // ─── LIMIT / OFFSET ────────────────────────────────────

    public function test_limit(): void
    {
        $this->seedDatabase();

        $result = $this->users()->limit(2)->get();

        $this->assertCount(2, $result);
    }

    public function test_skip_and_limit(): void
    {
        $this->seedDatabase();

        $result = $this->users()->skip(1)->limit(2)->get();

        $this->assertCount(2, $result);
        $this->assertSame('User 2', $result->first()->name);
    }

    public function test_top(): void
    {
        $this->seedDatabase();

        $result = $this->users()->top(3)->get();

        $this->assertCount(3, $result);
    }

    // ─── ORDER BY ──────────────────────────────────────────

    public function test_order_by_asc(): void
    {
        $this->seedDatabase();

        $result = $this->users()->orderBy('name', 'ASC')->get();
        $names = array_map(fn($u) => $u->name, $result->toArray());

        $this->assertSame(['User 1', 'User 2', 'User 3', 'User 4', 'User 5'], $names);
    }

    public function test_order_by_desc(): void
    {
        $this->seedDatabase();

        $result = $this->users()->orderBy('id', 'DESC')->get();
        $ids = array_map(fn($u) => $u->id, $result->toArray());

        $this->assertSame([5, 4, 3, 2, 1], $ids);
    }

    // ─── AGGREGATES ────────────────────────────────────────

    public function test_count(): void
    {
        $this->seedDatabase();

        $this->assertSame(5, $this->users()->count());
    }

    public function test_count_with_where(): void
    {
        $this->seedDatabase();

        $this->assertSame(3, $this->users()->where('active = ?', [1])->count());
    }

    public function test_sum(): void
    {
        $this->seedDatabase();

        // ages = 21, 22, 23, 24, 25 → sum = 115
        $this->assertSame(115, $this->users()->sum('age'));
    }

    public function test_avg(): void
    {
        $this->seedDatabase();

        // ages = 21, 22, 23, 24, 25 → avg = 23
        $this->assertSame(23.0, $this->users()->avg('age'));
    }

    public function test_min(): void
    {
        $this->seedDatabase();
        $this->assertSame(21, $this->users()->min('age'));
    }

    public function test_max(): void
    {
        $this->seedDatabase();
        $this->assertSame(25, $this->users()->max('age'));
    }

    // ─── FIRST / VALUE / FIND ──────────────────────────────

    public function test_first(): void
    {
        $this->seedDatabase();

        $user = $this->users()->first();

        $this->assertNotNull($user);
        $this->assertSame('User 1', $user->name);
    }

    public function test_first_returns_null_when_no_result(): void
    {
        $user = $this->users()->where('id = ?', [999])->first();

        $this->assertNull($user);
    }

    public function test_value(): void
    {
        $this->seedDatabase();

        $name = $this->users()->where('id = ?', [3])->value('name');

        $this->assertSame('User 3', $name);
    }

    public function test_value_returns_default_when_no_result(): void
    {
        $name = $this->users()->where('id = ?', [999])->value('name', 'default');

        $this->assertSame('default', $name);
    }

    public function test_first_value(): void
    {
        $this->seedDatabase();

        $name = $this->users()->firstValue('name');

        $this->assertSame('User 1', $name);
    }

    // ─── CHUNK / PAGINATE / LAZY ──────────────────────────

    public function test_chunk(): void
    {
        $this->seedDatabase();

        $collected = [];
        $this->users()->chunk(2, function ($rows) use (&$collected) {
            foreach ($rows as $row) $collected[] = $row->id;
        });

        $this->assertCount(5, $collected);
    }

    public function test_chunk_stops_on_false(): void
    {
        $this->seedDatabase();

        $pages = 0;
        $this->users()->chunk(2, function ($rows) use (&$pages) {
            $pages++;
            return false;
        });

        $this->assertSame(1, $pages);
    }

    public function test_paginate(): void
    {
        $this->seedDatabase();

        $page = $this->users()->paginate(page: 1, size: 2);

        $this->assertSame(2, $page->items()->count());
        $this->assertSame(5, $page->total());
        $this->assertSame(3, $page->lastPage());
    }

    public function test_paginate_second_page(): void
    {
        $this->seedDatabase();

        $page = $this->users()->paginate(page: 2, size: 2);

        $this->assertSame(2, $page->items()->count());
    }

    public function test_simple_paginate(): void
    {
        $this->seedDatabase();

        $page = $this->users()->simplePaginate(page: 1, size: 2);

        $this->assertSame(2, $page->items()->count());
        $this->assertNull($page->total());
    }

    public function test_lazy(): void
    {
        $this->seedDatabase();

        $ids = [];
        foreach ($this->users()->lazy(chunk: 2) as $user) {
            $ids[] = $user->id;
        }

        $this->assertCount(5, $ids);
    }

    // ─── PLUCK ─────────────────────────────────────────────

    public function test_pluck_single_column(): void
    {
        $this->seedDatabase();

        $names = $this->users()->pluck('name');

        $this->assertSame(5, $names->count());
    }

    public function test_pluck_with_key(): void
    {
        $this->seedDatabase();

        $names = $this->users()->pluck('name', 'id');

        $this->assertSame('User 1', $names[1]);
    }

    // ─── EXISTS / DOESNT EXIST ─────────────────────────────

    public function test_exists_returns_true(): void
    {
        $this->seedDatabase();

        $this->assertTrue($this->users()->where('id = ?', [1])->exists());
    }

    public function test_exists_returns_false(): void
    {
        $this->seedDatabase();

        $this->assertFalse($this->users()->where('id = ?', [999])->exists());
    }

    public function test_doesnt_exist_returns_true(): void
    {
        $this->seedDatabase();

        $this->assertTrue($this->users()->where('id = ?', [999])->doesntExist());
    }

    // ─── DISTINCT ──────────────────────────────────────────

    public function test_distinct(): void
    {
        $this->seedDatabase();

        $result = $this->users()->distinct()->get('active');
        $actives = array_map(fn($u) => $u->active, $result->toArray());

        $this->assertCount(2, $result);
        $this->assertSame([1, 0], array_values(array_unique($actives)));
    }

    // ─── WHEN / UNLESS ─────────────────────────────────────

    public function test_when_condition_true(): void
    {
        $this->seedDatabase();

        $result = $this->users()->when(true, fn($q) => $q->where('id = ?', [1]))->get();

        $this->assertCount(1, $result);
    }

    public function test_when_condition_false(): void
    {
        $this->seedDatabase();

        $result = $this->users()->when(false, fn($q) => $q->where('id = ?', [1]))->get();

        $this->assertCount(5, $result);
    }
}