<?php

declare(strict_types=1);

namespace Database\Test\Feature\BugTracking;

use Database\Test\Feature\DatabaseTestCase;

final class CaptureEventsBugTest extends DatabaseTestCase
{
    /**
     * BUG #9 : CaptureEvents::triggerEvent() fait :
     *   if ( strrpos($name, 'ing') ) $this->lock();
     *
     * strrpos() retourne int|false. Si 'ing' est à la position 0 (impossible
     * en pratique), le if est faux. Mais surtout : 'ing' est TOUJOURS
     * présent dans 'creating', 'saving', 'updating'... → lock() est appelé.
     *
     * Le vrai bug : la condition `if (strrpos(...))` est ambiguë.
     * Elle devrait être `if (strrpos(...) !== false)`.
     *
     * Test nécessite un event dont le nom commence par 'ing' (rare).
     */
    public function test_bug_9_trigger_event_condition_is_correct(): void
    {
        $this->markTestSkipped(
            'BUG #9 identifié : CaptureEvents::triggerEvent() utilise '
            . 'if (strrpos($name, "ing")) au lieu de if (strrpos(...) !== false). '
            . 'Test nécessite un event personnalisé commençant par "ing".'
        );
    }
}