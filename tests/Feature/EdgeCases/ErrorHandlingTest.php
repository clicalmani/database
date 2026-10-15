<?php

declare(strict_types=1);

namespace Database\Test\Feature\EdgeCases;

use Clicalmani\Core\Support\Facades\DB;
use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class ErrorHandlingTest extends DatabaseTestCase
{
    public function test_duplicate_unique_throws(): void
    {
        $this->seedDatabase();

        $this->expectException(\PDOException::class);

        User::create([
            'name'  => 'Dup',
            'email' => 'user1@example.com',   // déjà pris
            'age'   => 20,
            'active' => 1,
        ]);
    }

    public function test_invalid_sql_throws(): void
    {
        $this->expectException(\PDOException::class);

        DB::query('INVALID SQL STATEMENT');
    }

    public function test_where_nonexistent_column_throws(): void
    {
        $this->seedDatabase();

        $this->expectException(\PDOException::class);

        User::where('nonexistent_column = ?', [1])->get();
    }

    public function test_delete_without_where_throws(): void
    {
        $this->seedDatabase();

        $this->expectException(\Error::class);

        User::forceDelete();
    }

    public function test_save_without_attributes_returns_false_or_noop(): void
    {
        $user = new User;

        // Aucun attribut → rien à insérer
        $result = $user->save();

        $this->assertIsBool($result);
    }

    public function test_error_after_invalid_insert(): void
    {
        try {
            User::create([
                'name' => null,   // NOT NULL violation
                'email' => 'x@example.com',
                'age' => 20,
                'active' => 1,
            ]);
            $this->fail('Devrait throw');
        } catch (\PDOException $e) {
            // Vérifier que la transaction est dans un état cohérent
            $this->assertSame(0, $this->countRows('users'));
        }
    }

    public function test_recover_after_error(): void
    {
        try {
            DB::query('INVALID SQL');
        } catch (\PDOException $e) {
            // Ignore
        }

        // L'ORM doit rester utilisable
        $this->seedDatabase();

        $this->assertSame(5, $this->countRows('users'));
    }
}