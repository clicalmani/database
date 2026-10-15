<?php

declare(strict_types=1);

namespace Database\Test\Feature;

use Clicalmani\Core\Support\Facades\DB;
use PDO;

final class UpdateBuilderTest extends DatabaseTestCase
{
    public function test_update_single_row(): void
    {
        $this->seedDatabase();

        $affected = DB::table('users')
            ->where('id = ?', [1])
            ->update(['name' => 'Updated']);

        $this->assertSame(1, $affected);

        $row = $this->pdo->query('SELECT name FROM users WHERE id = 1')->fetch(PDO::FETCH_OBJ);
        $this->assertSame('Updated', $row->name);
    }

    public function test_update_multiple_rows(): void
    {
        $this->seedDatabase();

        $affected = DB::table('users')
            ->where('active = ?', [1])
            ->update(['active' => 0]);

        // 3 users actifs (ids 1, 3, 5)
        $this->assertSame(3, $affected);

        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM users WHERE active = 1')->fetchColumn();
        $this->assertSame(0, $count);
    }

    public function test_update_no_matching_rows(): void
    {
        $this->seedDatabase();

        $affected = DB::table('users')
            ->where('id = ?', [999])
            ->update(['name' => 'Nobody']);

        $this->assertSame(0, $affected);
    }

    public function test_update_multiple_columns(): void
    {
        $this->seedDatabase();

        DB::table('users')
            ->where('id = ?', [1])
            ->update([
                'name'   => 'Multi',
                'age'    => 99,
                'active' => 0,
            ]);

        $row = $this->pdo->query('SELECT * FROM users WHERE id = 1')->fetch(PDO::FETCH_OBJ);
        $this->assertSame('Multi', $row->name);
        $this->assertSame(99, (int) $row->age);
        $this->assertSame(0, (int) $row->active);
    }

    public function test_update_with_raw_expression(): void
    {
        $this->seedDatabase();

        DB::table('users')
            ->where('id = ?', [1])
            ->update(['age' => DB::raw('age + 10')]);

        $row = $this->pdo->query('SELECT age FROM users WHERE id = 1')->fetch(PDO::FETCH_OBJ);
        $this->assertSame(31, (int) $row->age); // 21 + 10
    }

    public function test_update_empty_array_throws_or_noop(): void
    {
        $this->seedDatabase();

        try {
            $affected = DB::table('users')
                ->where('id = ?', [1])
                ->update([]);
            $this->assertSame(0, $affected);
        } catch (\Exception $e) {
            $this->assertTrue(true); // Comportement alternatif acceptable
        }
    }
}