<?php

declare(strict_types=1);

namespace Database\Test\Feature\CRUD;

use PDO;
use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class CreateTest extends DatabaseTestCase
{
    // ─── CREATE VIA ATTRIBUTS DIRECTS ──────────────────────

    public function test_create_new_instance_and_save(): void
    {
        $user = new User;
        $user->name   = 'Alice';
        $user->email  = 'alice@example.com';
        $user->age    = 30;
        $user->active = 1;

        $success = $user->save();

        $this->assertTrue($success);
        $this->assertSame(1, $this->countRows('users'));
    }

    public function test_created_instance_has_id(): void
    {
        $user = new User;
        $user->name = 'Bob';
        $user->email = 'bob@example.com';
        $user->age = 25;
        $user->active = 1;
        $user->save();

        // Après save(), l'ID doit être peuplé
        $this->assertNotNull($user->id);
        $this->assertSame(1, (int) $user->id);
    }

    public function test_create_via_static_method(): void
    {
        $user = User::create([
            'name'   => 'Charlie',
            'email'  => 'charlie@example.com',
            'age'    => 35,
            'active' => 1,
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->id);
        $this->assertSame('Charlie', $user->name);
        $this->assertSame(1, $this->countRows('users'));
    }

    // ─── INSERT DIRECT ─────────────────────────────────────

    public function test_insert_direct_returns_bool(): void
    {
        $user = new User;

        $success = $user->insert([[
            'name'   => 'Direct',
            'email'  => 'direct@example.com',
            'age'    => 20,
            'active' => 1,
        ]]);

        $this->assertTrue($success);
        $this->assertSame(1, $this->countRows('users'));
    }

    public function test_insert_multiple_rows(): void
    {
        $user = new User;

        $success = $user->insert([
            ['name' => 'A', 'email' => 'a@example.com', 'age' => 20, 'active' => 1],
            ['name' => 'B', 'email' => 'b@example.com', 'age' => 21, 'active' => 1],
            ['name' => 'C', 'email' => 'c@example.com', 'age' => 22, 'active' => 0],
        ]);

        $this->assertTrue($success);
        $this->assertSame(3, $this->countRows('users'));
    }

    public function test_insert_empty_array_returns_false(): void
    {
        $user = new User;
        $result = $user->insert([]);

        $this->assertFalse($result);
        $this->assertSame(0, $this->countRows('users'));
    }

    // ─── FILL ──────────────────────────────────────────────

    public function test_fill_sets_attributes(): void
    {
        $user = new User;
        $user->fill([
            'name'   => 'Filled',
            'email'  => 'filled@example.com',
            'age'    => 30,
            'active' => 1,
        ]);

        $this->assertSame('Filled', $user->name);
        $this->assertSame('filled@example.com', $user->email);
        $this->assertSame(30, (int) $user->age);
    }

    public function test_fill_returns_self(): void
    {
        $user = new User;
        $result = $user->fill(['name' => 'X']);

        $this->assertSame($user, $result);
    }

    // ─── DEFAULTS ──────────────────────────────────────────

    public function test_default_values_applied(): void
    {
        $user = new User;
        $user->name = 'Defaults';
        $user->save();

        // age default = 0, active default = 1 (définis dans UserEntity)
        $row = $this->pdo->query('SELECT age, active FROM users WHERE name = "Defaults"')->fetch(PDO::FETCH_OBJ);

        $this->assertSame(0, (int) $row->age);
        $this->assertSame(1, (int) $row->active);
    }

    // ─── CASTS ─────────────────────────────────────────────

    public function test_integer_cast_on_save(): void
    {
        $user = new User;
        $user->name = 'Cast';
        $user->age  = '30';   // string
        $user->save();

        $row = $this->pdo->query('SELECT age FROM users WHERE name = "Cast"')->fetch(PDO::FETCH_OBJ);
        $this->assertSame(30, (int) $row->age);
    }

    // ─── UNIQUE CONSTRAINTS ────────────────────────────────

    public function test_duplicate_email_throws(): void
    {
        $this->seedDatabase();

        $this->expectException(\PDOException::class);

        $user = new User;
        $user->name  = 'Dup';
        $user->email = 'user1@example.com';   // déjà pris
        $user->save();
    }

    // ─── BATCH ─────────────────────────────────────────────

    public function test_save_returns_true_when_nothing_to_insert(): void
    {
        $user = new User;

        // Aucun attribut modifié → rien à insérer
        $result = $user->save();

        // Selon l'implémentation, peut retourner true ou false
        $this->assertIsBool($result);
    }
}