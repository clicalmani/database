<?php

declare(strict_types=1);

namespace Database\Test\Feature\BugTracking;

use Database\Test\Feature\DatabaseTestCase;

final class MorphToManyBugTest extends DatabaseTestCase
{
    /**
     * BUG #10 : MorphToMany::detach() fait :
     *   DB::table($this->table)->delete()->where(...)
     *   // delete() retourne bool, pas un builder
     *
     * → Fatal error : Call to a member function where() on bool
     *
     * Test nécessite : modèle Comment + table commentables.
     */
    public function test_bug_10_detach_chains_delete_and_where(): void
    {
        $this->markTestSkipped(
            'BUG #10 identifié : MorphToMany::detach() chaîne delete()->where(), '
            . 'mais delete() retourne un bool. Test nécessite un modèle Comment.'
        );
    }

    /**
     * BUG #11 : MorphToMany::attach() retourne un objet, pas un bool.
     */
    public function test_bug_11_attach_returns_bool(): void
    {
        $this->markTestSkipped(
            'BUG #11 identifié : MorphToMany::attach() retourne un AbstractInsertStatement, '
            . 'pas un bool. Test nécessite un modèle Comment.'
        );
    }

    /**
     * BUG #12 : MorphToMany::get() utilise $this->related->get() au lieu de $this->query->get().
     */
    public function test_bug_12_get_uses_query_not_related(): void
    {
        $this->markTestSkipped(
            'BUG #12 identifié : MorphToMany::get() appelle $this->related->get(), '
            . 'ignorant les clauses selectRaw/joinInner/where construites.'
        );
    }
}