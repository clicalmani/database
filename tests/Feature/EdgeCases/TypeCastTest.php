<?php

declare(strict_types=1);

namespace Database\Test\Feature\EdgeCases;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class TypeCastTest extends DatabaseTestCase
{
    public function test_integer_string_is_cast(): void
    {
        $user = new User;
        $user->name  = 'Cast';
        $user->email = 'cast@example.com';
        $user->age   = '30';   // string
        $user->save();

        $found = User::where('name = ?', ['Cast'])->first();
        $this->assertIsInt($found->age);
        $this->assertSame(30, $found->age);
    }

    public function test_boolean_cast_to_int(): void
    {
        $user = new User;
        $user->name  = 'Bool';
        $user->email = 'bool@example.com';
        $user->age   = 20;
        $user->active = true;   // bool
        $user->save();

        $found = User::where('name = ?', ['Bool'])->first();
        $this->assertSame(1, (int) $found->active);
    }

    public function test_float_is_cast_to_int_for_integer_column(): void
    {
        $user = new User;
        $user->name  = 'Float';
        $user->email = 'float@example.com';
        $user->age   = 30.7;   // float
        $user->save();

        $found = User::where('name = ?', ['Float'])->first();
        $this->assertSame(31, (int) $found->age);
    }

    public function test_reading_string_returns_string(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $this->assertIsString($user->name);
    }

    public function test_reading_integer_returns_integer(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $this->assertIsInt($user->age);
        $this->assertIsInt($user->active);
    }

    public function test_null_preserved_through_hydration(): void
    {
        $user = new User;
        $user->name  = 'NullPreserved';
        $user->email = null;
        $user->age   = 20;
        $user->save();

        $found = User::where('name = ?', ['NullPreserved'])->first();
        $this->assertNull($found->email);
    }

    public function test_update_with_string_when_int_expected(): void
    {
        $this->seedDatabase();

        $user = User::find(1);
        $user->age = '99';   // string
        $user->save();

        $row = $this->pdo->query('SELECT age FROM users WHERE id = 1')->fetch(\PDO::FETCH_OBJ);
        $this->assertSame(99, (int) $row->age);
    }
}