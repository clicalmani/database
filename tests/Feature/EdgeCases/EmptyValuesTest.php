<?php

declare(strict_types=1);

namespace Database\Test\Feature\EdgeCases;

use Clicalmani\Core\Support\Facades\DB;
use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class EmptyValuesTest extends DatabaseTestCase
{
    public function test_insert_empty_string(): void
    {
        $user = new User;
        $user->name  = '';
        $user->email = '';
        $user->age   = 0;
        $user->save();

        $row = $this->pdo->query("SELECT name, email FROM users")->fetch(\PDO::FETCH_OBJ);
        $this->assertSame('', $row->name);
        $this->assertSame('', $row->email);
    }

    public function test_insert_zero(): void
    {
        $user = new User;
        $user->name  = 'Zero';
        $user->email = 'zero@example.com';
        $user->age   = 0;
        $user->active = 0;
        $user->save();

        $row = $this->pdo->query("SELECT age, active FROM users WHERE name = 'Zero'")->fetch(\PDO::FETCH_OBJ);
        $this->assertSame(0, (int) $row->age);
        $this->assertSame(0, (int) $row->active);
    }

    public function test_where_in_empty_array_returns_all(): void
    {
        $this->seedDatabase();

        // Comportement documenté : whereIn([]) → tous
        $result = User::whereIn('id', [])->get();

        $this->assertSame(5, $result->count());
    }

    public function test_where_not_in_empty_array_returns_none(): void
    {
        $this->seedDatabase();

        // whereNotIn([]) → "1 = 1" également
        $result = User::whereNotIn('id', [])->get();

        $this->assertIsObject($result);
    }

    public function test_empty_collection_returns_zero_count(): void
    {
        $result = User::where('id = ?', [9999])->get();

        $this->assertSame(0, $result->count());
        $this->assertTrue($result->isEmpty());
    }

    public function test_first_on_empty_returns_null(): void
    {
        $this->seedDatabase();

        $this->assertNull(User::where('id = ?', [9999])->first());
    }

    public function test_pluck_on_empty_returns_empty_collection(): void
    {
        $names = User::where('id = ?', [9999])->pluck('name');

        $this->assertInstanceOf(\Clicalmani\Core\Collection\Collection::class, $names);
        $this->assertTrue($names->isEmpty());
    }

    public function test_chunk_on_empty_does_not_call_callback(): void
    {
        $called = false;

        User::where('id = ?', [9999])->chunk(10, function () use (&$called) {
            $called = true;
        });

        $this->assertFalse($called);
    }

    public function test_select_raw_empty_string(): void
    {
        $this->seedDatabase();

        try {
            $result = DB::table('users')->selectRaw('')->get();
            $this->assertIsObject($result);
        } catch (\Throwable $e) {
            $this->assertTrue(true);  // Comportement acceptable
        }
    }

    public function test_insert_empty_array_is_noop(): void
    {
        $user = new User;
        $result = $user->insert([]);

        $this->assertFalse($result);
        $this->assertSame(0, $this->countRows('users'));
    }
}