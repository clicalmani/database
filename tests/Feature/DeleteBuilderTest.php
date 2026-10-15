<?php

declare(strict_types=1);

namespace Database\Test\Feature;

use Clicalmani\Core\Support\Facades\DB;

final class DeleteBuilderTest extends DatabaseTestCase
{
    public function test_delete_single_row(): void
    {
        $this->seedDatabase();

        $success = DB::table('users')
            ->where('id = ?', [1])
            ->delete();

        $this->assertTrue($success);
        $this->assertSame(4, $this->countRows('users'));
    }

    public function test_delete_multiple_rows(): void
    {
        $this->seedDatabase();

        $success = DB::table('users')
            ->where('active = ?', [1])
            ->delete();

        $this->assertTrue($success);
        // 3 users actifs supprimés
        $this->assertSame(2, $this->countRows('users'));
    }

    public function test_delete_no_matching_rows(): void
    {
        $this->seedDatabase();

        $success = DB::table('users')
            ->where('id = ?', [999])
            ->delete();

        // Selon l'implémentation, peut retourner true (0 rows affected) ou false
        $this->assertIsBool($success);
        $this->assertSame(5, $this->countRows('users'));
    }

    public function test_delete_with_in_clause(): void
    {
        $this->seedDatabase();

        $success = DB::table('users')
            ->whereIn('id', [1, 2, 3])
            ->delete();

        $this->assertTrue($success);
        $this->assertSame(2, $this->countRows('users'));
    }

    public function test_delete_cascades_to_posts(): void
    {
        $this->seedDatabase();

        // 15 posts au total
        $this->assertSame(15, $this->countRows('posts'));

        // Supprime user 1 → cascade sur ses 3 posts
        DB::table('users')
            ->where('id = ?', [1])
            ->delete();

        $this->assertSame(12, $this->countRows('posts'));
    }

    public function test_truncate_empties_table(): void
    {
        $this->seedDatabase();
        $this->pdo->query('SET FOREIGN_KEY_CHECKS = 0');

        $success = DB::table('users')->truncate();

        $this->assertTrue($success);
        $this->assertSame(0, $this->countRows('users'));
    }

    public function test_delete_all_without_where_is_refused_or_deletes_all(): void
    {
        $this->seedDatabase();

        try {
            $success = DB::table('users')->delete();
            // Si pas d'exception, on vérifie que la table est vide
            $this->assertSame(0, $this->countRows('users'));
        } catch (\Exception $e) {
            // Comportement alternatif : suppression refusée
            $this->assertSame(5, $this->countRows('users'));
        }
    }
}