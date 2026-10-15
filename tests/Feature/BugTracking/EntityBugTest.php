<?php

declare(strict_types=1);

namespace Database\Test\Feature\BugTracking;

use Database\Test\Feature\DatabaseTestCase;
use Database\Test\Feature\Fixtures\Models\User;

final class EntityBugTest extends DatabaseTestCase
{
    /**
     * BUG #6 : Entity::serverVersion() est typée bool mais retourne une string.
     *   protected function serverVersion(): bool {
     *       ...
     *       return $version;  // ← string
     *   }
     *
     * Test : on utilise la réflexion pour appeler serverVersion() et vérifier
     * que le retour est bien une string (pas un bool).
     *
     * NOTE : En PHP 8, le typage strict de retour ne s'applique pas sur
     * les méthodes protected appelées via réflexion (contournement).
     * On teste via l'appel direct si possible, sinon on skip.
     */
    public function test_bug_6_server_version_returns_string(): void
    {
        $user = new User;

        $entity = $user->getEntity();
        $method = new \ReflectionMethod($entity, 'serverVersion');
        $method->setAccessible(true);

        try {
            $version = $method->invoke($entity);
            $this->assertIsString(
                $version,
                'BUG #6 : serverVersion() est typée bool mais retourne une string'
            );
        } catch (\TypeError $e) {
            // Si PHP respecte le typage strict, TypeError est levée
            $this->fail(
                'BUG #6 : serverVersion() déclare bool mais retourne string → TypeError'
            );
        }
    }
}