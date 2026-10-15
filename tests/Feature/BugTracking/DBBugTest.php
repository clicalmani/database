<?php

declare(strict_types=1);

namespace Database\Test\Feature\BugTracking;

use Clicalmani\Core\Support\Facades\DB;
use Database\Test\Feature\DatabaseTestCase;

final class DBBugTest extends DatabaseTestCase
{
    /**
     * BUG #5 : DB::select() passe $flags à fetchAll() avec spread.
     *   return $statement->fetchAll(...$flags);
     *
     * fetchAll() attend (int $mode, mixed ...$args).
     * Si $flags = [PDO::FETCH_ASSOC], le spread donne fetchAll(PDO::FETCH_ASSOC).
     * Si $flags = [PDO::FETCH_CLASS, 'User'], le spread donne fetchAll(PDO::FETCH_CLASS, 'User').
     * Mais dans select(), $flags est OPTIONNEL (default []).
     *   → Si $flags = [], fetchAll() sans argument.
     *
     * Test : select() avec PDO::FETCH_ASSOC explicite.
     */
    public function test_bug_5_select_with_fetch_assoc(): void
    {
        $this->seedDatabase();

        $rows = DB::select(
            'SELECT * FROM users WHERE id = ?',
            [1],
            [\PDO::FETCH_ASSOC]
        );

        $this->assertIsArray($rows->toArray());
        $this->assertNotEmpty($rows->toArray());
    }

    /**
     * BUG #5 bis : même problème sur selectOne().
     */
    public function test_bug_5_select_one_with_fetch_assoc(): void
    {
        $this->seedDatabase();

        $row = DB::selectOne(
            'SELECT * FROM users WHERE id = ?',
            [1],
            [\PDO::FETCH_ASSOC]
        );

        $this->assertIsArray($row);
    }
}