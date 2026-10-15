<?php

declare(strict_types=1);

namespace Database\Test\Feature\BugTracking;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class SQLAggregateBugTest extends DatabaseTestCase
{
    /**
     * BUG #16 : SQLAggregate::avg() est typée float mais DBQuery::avg() peut
     * retourner null (si aucun résultat).
     *
     *   public function avg(string $field) : float {
     *       return $this->query->avg($field);  // ← ?float
     *   }
     *
     * Test : avg() sur une requête vide → TypeError ou null ?
     */
    public function test_bug_16_avg_on_empty_returns_null(): void
    {
        $this->seedDatabase();

        try {
            $result = User::where('id = ?', [999])->avg('age');

            // Si on arrive ici, la méthode a retourné null (pas de TypeError)
            $this->assertNull(
                $result,
                'BUG #16 : avg() devrait retourner null sur requête vide'
            );
        } catch (\TypeError $e) {
            $this->fail(
                'BUG #16 : avg() est typée float mais retourne null → TypeError'
            );
        }
    }

    /**
     * BUG #16 bis : même problème sur sum().
     */
    public function test_bug_16_sum_on_empty_returns_null(): void
    {
        $this->seedDatabase();

        try {
            $result = User::where('id = ?', [999])->sum('age');
            $this->assertNull($result, 'BUG #16 bis : sum() doit retourner null sur vide');
        } catch (\TypeError $e) {
            $this->fail('BUG #16 bis : sum() typée float mais retourne null');
        }
    }

    /**
     * BUG #16 ter : idem sur min() et max().
     */
    public function test_bug_16_min_max_on_empty_return_null(): void
    {
        $this->seedDatabase();

        try {
            $min = User::where('id = ?', [999])->min('age');
            $max = User::where('id = ?', [999])->max('age');

            $this->assertNull($min);
            $this->assertNull($max);
        } catch (\TypeError $e) {
            $this->fail('BUG #16 ter : min()/max() typées float mais retournent null');
        }
    }
}