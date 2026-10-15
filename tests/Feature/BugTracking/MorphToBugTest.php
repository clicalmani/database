<?php

declare(strict_types=1);

namespace Database\Test\Feature\BugTracking;

use Database\Test\Feature\DatabaseTestCase;

final class MorphToBugTest extends DatabaseTestCase
{
    /**
     * BUG #13 : MorphTo::getParentKeys() utilise $this->parentType et
     * $this->parentId, qui sont fixés dans le __construct du modèle courant.
     *
     * En eager loading, tous les modèles devraient recevoir leurs propres clés.
     * Or ici, getParentKeys() reçoit un array $models mais ignore leurs valeurs
     * et utilise celles du modèle courant ($this->model).
     *
     * Test nécessite : un modèle polymorphique (Comment).
     */
    public function test_bug_13_get_parent_keys_uses_passed_models(): void
    {
        $this->markTestSkipped(
            'BUG #13 identifié : MorphTo::getParentKeys() ignore l\'array $models '
            . 'et utilise $this->parentId/$this->parentType du modèle courant. '
            . 'Test nécessite un modèle polymorphique.'
        );
    }
}