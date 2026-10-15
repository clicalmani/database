<?php
namespace Clicalmani\Database\Listeners;

use Clicalmani\Database\Events\QueryEvent;

/**
 * Registre central des listeners de requêtes SQL.
 *
 * Un listener est un callable : function(string $sql, array $bindings, float $timeMs): void
 */
final class QueryListenerRegistry
{
    /** @var array<int, callable> */
    private array $listeners = [];

    private int $nextId = 0;

    private static ?self $instance = null;

    public static function getInstance() : self
    {
        return static::$instance ??= new self();
    }

    /**
     * Enregistre un listener et retourne un callable de désinscription.
     */
    public function register(callable $listener): callable
    {
        $id = $this->nextId++;

        $this->listeners[$id] = $listener;

        return function () use ($id): void {
            $this->unregister($id);
        };
    }

    public function unregister(int $id): void
    {
        unset($this->listeners[$id]);
    }

    public function clear(): void
    {
        $this->listeners = [];
    }

    /**
     * Émet un événement à tous les listeners.
     * Les exceptions des listeners sont avalées (ne cassent pas la requête).
     */
    public function dispatch(QueryEvent $event): void
    {
        foreach ($this->listeners as $listener) {
            $listener($event->sql, $event->bindings, $event->timeMs);
            if ($event->error) {
                logger()->warning('⚠️ Query listener error: ' . $event->error->getMessage(), [
                    'exception' => $event->error,
                    'sql' => $event->sql,
                    'bindings' => $event->bindings,
                    'timeMs' => $event->timeMs,
                ]);
            }
        }
    }

    public function hasListeners(): bool
    {
        return !empty($this->listeners);
    }
}