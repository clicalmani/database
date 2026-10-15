<?php

declare(strict_types=1);

namespace Database\Test\Feature\EdgeCases;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class MassAssignmentTest extends DatabaseTestCase
{
    public function test_fill_accepts_all_when_no_fillable_defined(): void
    {
        $user = new User;
        $user->fill([
            'name'  => 'Filled',
            'email' => 'filled@example.com',
            'age'   => 20,
        ]);

        $this->assertSame('Filled', $user->name);
        $this->assertSame('filled@example.com', $user->email);
        $this->assertSame(20, (int) $user->age);
    }

    public function test_fill_ignores_unknown_attributes(): void
    {
        $user = new User;
        $user->fill([
            'name'          => 'Known',
            'email' => 'created@example.com',
            'unknown_field' => 'Ignored',
        ]);

        // 'unknown_field' n'existe pas dans l'entité
        $this->assertSame('Known', $user->name);
    }

    public function test_create_with_mass_assignment(): void
    {
        $user = User::create([
            'name'  => 'Created',
            'email' => 'created@example.com',
            'age'   => 25,
            'active' => 1,
        ]);

        $this->assertSame('Created', $user->name);
        $this->assertSame(1, $this->countRows('users'));
    }

    public function test_fill_returns_self_for_chaining(): void
    {
        $user = new User;
        $result = $user->fill(['name' => 'Chain', 'email' => 'created@example.com']);

        $this->assertSame($user, $result);
    }

    public function test_fill_with_empty_array_is_noop(): void
    {
        $user = new User;
        $user->name = 'Original';
        $user->email = 'created@example.com';

        $user->fill([]);

        $this->assertSame('Original', $user->name);
    }
}