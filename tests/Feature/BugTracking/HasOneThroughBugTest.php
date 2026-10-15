<?php

declare(strict_types=1);

namespace Database\Test\Feature\BugTracking;

use Database\Test\Feature\DatabaseTestCase;

final class HasOneThroughBugTest extends DatabaseTestCase
{
    /**
     * BUG #14 : HasOneThrough::get() retourne $this->farModel (modèle vide)
     * si aucun résultat, au lieu de null.
     *   $this->result = $this->farModel->top(1)->get($fields)->first() ?? $this->farModel;
     *
     * Sémantique douteuse : un modèle vide n'est pas null.
     *
     * Test nécessite : modèle User → Profile → Log par exemple.
     */
    public function test_bug_14_returns_null_when_not_found(): void
    {
        $this->markTestSkipped(
            'BUG #14 identifié : HasOneThrough::get() retourne un farModel vide '
            . 'au lieu de null. Test nécessite un modèle intermédiaire.'
        );
    }

    /**
     * BUG #14 bis : même problème sur HasOne::get() :
     *   $this->result = $this->related->top(1)->get($fields)->first() ?? $this->related;
     */
    public function test_bug_14_bis_has_one_returns_null_when_not_found(): void
    {
        $this->markTestSkipped(
            'BUG #14 bis identifié : HasOne::get() retourne un related vide '
            . 'au lieu de null.'
        );
    }
}