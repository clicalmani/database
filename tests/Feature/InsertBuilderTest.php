<?php

declare(strict_types=1);

namespace Database\Test\Feature;

use Clicalmani\Core\Support\Facades\DB;

final class InsertBuilderTest extends DatabaseTestCase
{
    public function test_insert_single_row(): void
    {
        $stmt = DB::table('users')->insert([
            'name'   => 'Alice',
            'email'  => 'alice@example.com',
            'age'    => 30,
            'active' => 1,
        ]);
        $stmt->query();

        $this->assertSame('success', $stmt->status());
        $this->assertSame(1, $this->countRows('users'));
    }

    public function test_insert_returns_insert_id(): void
    {
        $id = DB::table('users')->insert([
            'name' => 'Bob',
        ])->getId();

        $this->assertSame(1, (int) $id);
        $this->assertSame(1, $this->countRows('users'));
    }

    public function test_insert_multiple_rows(): void
    {
        $stmt = DB::table('users')->insert([
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ]);
        $stmt->query();

        $this->assertSame('success', $stmt->status());
        $this->assertSame(3, $this->countRows('users'));
    }

    public function test_insert_empty_array_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DB::table('users')->insert([])->query();
    }

    public function test_insert_mismatched_columns_throws(): void
    {
        $this->expectException(\Exception::class);

        // Ligne 1 : 1 colonne, Ligne 2 : 2 colonnes
        DB::table('users')->insert([
            ['name' => 'A'],
            ['name' => 'B', 'email' => 'b@example.com'],
        ])->query();
    }

    public function test_insert_with_raw_value(): void
    {
        $stmt = DB::table('users')->insert([
            'name'       => 'Charlie',
            'created_at' => DB::raw('CURRENT_TIMESTAMP'),
        ]);
        $stmt->query();
        
        $this->assertSame('success', $stmt->status());
        $this->assertSame(1, $this->countRows('users'));
    }

    public function test_insert_affected_rows(): void
    {
        $stmt = DB::table('users')->insert([
            'name' => 'Test',
        ]);
        $stmt->query();

        $this->assertSame(1, $stmt->affectedRows());
    }
}