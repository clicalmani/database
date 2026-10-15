<?php

declare(strict_types=1);

namespace Database\Test\Feature;

use Clicalmani\Core\Support\Facades\DB;

final class SelectBuilderTest extends DatabaseTestCase
{
    public function test_simple_select_star(): void
    {
        $sql = (string) DB::table('users')->getBuilder()?->getSQL();
        // Le builder n'est pas encore exécuté, on teste via une requête réelle
        $this->seedDatabase();
        $result = DB::table('users')->get();
        $this->assertCount(5, $result);
    }

    public function test_select_specific_fields(): void
    {
        $this->seedDatabase();

        $result = DB::table('users')->get('name');

        $this->assertSame(5, $result->count());
        $this->assertObjectHasProperty('name', $result->first());
    }

    public function test_select_with_where(): void
    {
        $this->seedDatabase();

        $result = DB::table('users')->where('id = ?', [1])->get();

        $this->assertCount(1, $result);
    }

    public function test_select_with_order_by(): void
    {
        $this->seedDatabase();

        $result = DB::table('users')->orderBy('id', 'DESC')->get();
        $ids = array_map(fn($u) => $u->id, $result->toArray());

        $this->assertSame([5, 4, 3, 2, 1], $ids);
    }

    public function test_select_with_limit(): void
    {
        $this->seedDatabase();

        $result = DB::table('users')->limit(2)->get();

        $this->assertCount(2, $result);
    }

    public function test_select_with_offset_and_limit(): void
    {
        $this->seedDatabase();

        $result = DB::table('users')->skip(1)->limit(2)->get();
        $ids = array_map(fn($u) => $u->id, $result->toArray());

        $this->assertSame([2, 3], $ids);
    }

    public function test_select_distinct(): void
    {
        $this->seedDatabase();

        $result = DB::table('users')->distinct()->get('active');

        // active = 0 ou 1 → 2 valeurs distinctes
        $this->assertCount(2, $result);
    }

    public function test_select_with_group_by(): void
    {
        $this->seedDatabase();

        // Groupe par active, compte les ids
        $result = DB::table('users')
            ->selectRaw('active, COUNT(*) as total')
            ->groupBy('active')
            ->get();

        $this->assertCount(2, $result);
    }

    public function test_select_with_having(): void
    {
        $this->seedDatabase();

        $result = DB::table('users')
            ->selectRaw('active, COUNT(*) as total')
            ->groupBy('active')
            ->having('total >= 2')
            ->get();

        $this->assertGreaterThan(0, $result->count());
    }

    public function test_select_with_inner_join(): void
    {
        $this->seedDatabase();

        $result = DB::table('users')
            ->joinInner('posts', 'posts.user_id', 'users.id')
            ->get();

        // 5 users × 3 posts = 15 lignes
        $this->assertSame(15, $result->count());
    }

    public function test_select_with_left_join(): void
    {
        $this->seedDatabase();

        // User 1 a 3 posts, users 2-5 aussi → 15
        $result = DB::table('users')
            ->joinLeft('posts', 'posts.user_id', 'users.id')
            ->get();

        $this->assertSame(15, $result->count());
    }

    public function test_select_returns_empty_collection_when_no_rows(): void
    {
        $this->seedDatabase();

        $result = DB::table('users')->where('id = ?', [999])->get();

        $this->assertSame(0, $result->count());
    }

    public function test_all_returns_collection(): void
    {
        $this->seedDatabase();

        $result = DB::table('users')->all();

        $this->assertInstanceOf(\Clicalmani\Core\Collection\Collection::class, $result);
        $this->assertSame(5, $result->count());
    }

    public function test_first_returns_object_or_null(): void
    {
        $this->seedDatabase();

        $user = DB::table('users')->first();

        $this->assertIsObject($user);
        $this->assertSame('User 1', $user->name);
    }

    public function test_first_returns_null_when_empty(): void
    {
        $user = DB::table('users')->where('id = ?', [999])->first();
        $this->assertNull($user);
    }

    public function test_select_one(): void
    {
        $this->seedDatabase();

        $user = DB::selectOne(
            'SELECT * FROM users WHERE id = ?',
            [1]
        );

        $this->assertIsObject($user);
    }

    public function test_count_returns_int(): void
    {
        $this->seedDatabase();

        $count = DB::table('users')->count();

        $this->assertIsInt($count);
        $this->assertSame(5, $count);
    }

    public function test_exists_returns_bool(): void
    {
        $this->seedDatabase();

        $this->assertTrue(DB::table('users')->where('id = ?', [1])->exists());
        $this->assertFalse(DB::table('users')->where('id = ?', [999])->exists());
    }

    public function test_pluck_returns_collection(): void
    {
        $this->seedDatabase();

        $names = DB::table('users')->pluck('name');

        $this->assertInstanceOf(\Clicalmani\Core\Collection\Collection::class, $names);
        $this->assertSame(5, $names->count());
    }

    public function test_chunk_yields_all_rows(): void
    {
        $this->seedDatabase();

        $seen = [];
        DB::table('users')->chunk(2, function ($rows) use (&$seen) {
            foreach ($rows as $row) $seen[] = $row->id;
        });

        $this->assertCount(5, $seen);
        $this->assertSame([1, 2, 3, 4, 5], $seen);
    }
}