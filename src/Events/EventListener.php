<?php
namespace Clicalmani\Database\Events;

use Clicalmani\Database\Factory\Models\Model;

abstract class EventListener implements EventListenerInterface
{
    /**
     * Event name holder
     * 
     * @var string
     */
    public const __NAME__ = '';

    /**
     * Event model
     * 
     * @var \Clicalmani\Database\Factory\Models\Model
     */
    protected $target;

    /**
     * Event handler
     * 
     * @var \Closure
     */
    protected $handler;

    /**
     * Check if event is handleable
     * 
     * @param string $event
     * @return bool
     */
    public function isHandleable(string $event) : bool
    {
        return $this->target->isEvent($event);
    }

    public function register(mixed $handler): void
    {
        $this->target->registerEvent(static::__NAME__, $handler);
    }

    public function handle(mixed $data): void
    {
        /**
         * TODO
         */
    }

    /**
     * Target setter
     * 
     * @param \Clicalmani\Database\Factory\Models\Model $target
     * @return void
     */
    public function setTarget(Model $target) : void
    {
        $this->target = $target;
    }

    /**
     * Handler setter
     * 
     * @param \Closure $handler
     * @return void
     */
    public function setHandler(\Closure $handler) : void
    {
        $this->handler = $handler;
    }
}