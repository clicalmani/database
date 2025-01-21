<?php
namespace Clicalmani\Database\Factory\Models;

trait CaptureEvents
{
    protected function creating(\Closure $callback) {
        (new \Clicalmani\Database\Events\CreatingEvent($this))->register($callback);
    }

    protected function created(\Closure $callback) {
        (new \Clicalmani\Database\Events\CreatedEvent($this))->register($callback);
    }

    protected function updating(\Closure $callback) {
        (new \Clicalmani\Database\Events\UpdatingEvent($this))->register($callback);
    }

    protected function updated(\Closure $callback) {
        (new \Clicalmani\Database\Events\UpdatedEvent($this))->register($callback);
    }

    protected function deleting(\Closure $callback) {
        (new \Clicalmani\Database\Events\DeletingEvent($this))->register($callback);
    }

    protected function deleted(\Closure $callback) {
        (new \Clicalmani\Database\Events\DeletedEvent($this))->register($callback);
    }

    protected function restoring(\Closure $callback) {
        (new \Clicalmani\Database\Events\RestoringEvent($this))->register($callback);
    }

    protected function restored(\Closure $callback) {
        (new \Clicalmani\Database\Events\RestoredEvent($this))->register($callback);
    }

    protected function saving(\Closure $callback) {
        (new \Clicalmani\Database\Events\SavingEvent($this))->register($callback);
    }

    protected function saved(\Closure $callback) {
        (new \Clicalmani\Database\Events\SavedEvent($this))->register($callback);
    }

    private function getEventHandler(string $name) : mixed
    {
        if ( $this->isCustomEvent($name) ) {
            foreach ($this->dispatchesEvents as $listener) {
                if ($listener::__NAME__ === $name) return $listener;
            }
        }

        return @$this->eventHandlers[$name];
    }

    /**
     * @throws \RuntimeException
     */
    private function triggerEvent(string $name, mixed $data = null) : void
    {
        /** @var callable|string */
        $handler = $this->getEventHandler($name);

        /**
         * |-------------------------------------------------------
         * | Trigger Built-In Events
         * |-------------------------------------------------------
         * 
         * Built-in events are handled through callback function. The
         * callback function receive the model instance as its unique
         * argument.
         * 
         * 
         */
        if ( FALSE === $this->isCustomEvent($name) ) {
            /**
             * Check for pre-runtime events such as
             * creating, updating, deleting and place
             * a lock on the table in writing mode
             */
            if ( strrpos($name, 'ing') ) $this->lock();
            
            if ( is_callable($handler) ) $handler($this);

            /**
             * Release the lock
             */
            if ( $this->isLocked() ) $this->unlock();
        }

        /**
         * |-------------------------------------------------------
         * | Trigger Custom Events
         * |-------------------------------------------------------
         * 
         * Custom event may be a class that inherit from the
         * Event class or callback function.
         */
        else {
            foreach ($this->dispatchesEvents as $listener) {
                
                if ($listener::__NAME__ !== $name) {

                    if ( class_exists($handler) ) {
                        /** @var \Clicalmani\Database\Events\EventListener */
                        $listener = new $handler;
                        $listener->setTarget($this);
                        $listener->handle($data);
                    } throw new \RuntimeException("Class $handler not found.");
                }
            }
        }
    }

    /**
     * Check event
     * 
     * @param string $name
     * @return bool
     */
    public function isEvent(string $name) : bool
    {
        $builtin_events = [
            'creating',
            'created',
            'updating',
            'updated',
            'deleting',
            'deleted',
            'saving',
            'saved',
            'restoring',
            'restored'
        ];

        if ( in_array($name, $builtin_events) || $this->isCustomEvent($name) ) return true;

        return false;
    }

    public function isCustomEvent(string $name) 
    {
        foreach ($this->dispatchesEvents as $class) {
            if ($class::__NAME__ === $name) return true;
        }

        return false;
    }

    protected function booted() : void
    {
        /**
         * TODO
         */
    }
}