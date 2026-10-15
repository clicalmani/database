<?php

declare(strict_types=1);

namespace Database\Test\Feature\BugTracking;

use Clicalmani\Core\Support\Facades\DB;
use Database\Test\Feature\DatabaseTestCase;

final class DBQueryBugTest extends DatabaseTestCase
{
    /**
     * BUG #4 : DBQuery::all() écrase le where existant.
     *   public function all() : Collection {
     *       $this->params['where'] = 'TRUE';  // ← écrase
     *       return $this->exec()->result();
     *   }
     *
     * Test : on ajoute un where, puis on appelle all() → le where doit survivre.
     */
    public function test_bug_4_all_does_not_discard_where(): void
    {
        $this->seedDatabase();
        
        $query = DB::table('users')->where('id = ?', [1]);
        $result = $query->all();
        
        $this->assertSame(
            5,
            $result->count(),
            'BUG #4 : all() écrase le where existant'
        );
    }

    /**
     * BUG #7 : DBQuery::firstValue() n'a pas de garde null.
     *   return $result->result()->first()[$field];  // ← null si aucun résultat
     *
     * Test : firstValue() sur une requête vide → doit retourner null, pas crash.
     */
    public function test_bug_7_first_value_on_empty_returns_null(): void
    {
        $this->seedDatabase();

        $value = DB::table('users')->where('id = ?', [999])->firstValue('name');

        $this->assertNull($value, 'BUG #7 : firstValue() doit retourner null sur requête vide');
    }
}