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
            return $this->getObserver($name);
        }

        /**
         * For built-in events, model event has priority over
         * observer.
         */
        if ( array_key_exists($name, $this->eventHandlers) ) {
            return $this->eventHandlers[$name];
        }

        return $this->getObserver($name);
    }

    /**
     * @throws \RuntimeException
     */
    private function triggerEvent(string $name, mixed $data = null) : void
    {
        /** @var callable|string */
        $handler = $this->getEventHandler($name);
        // logger()->debug([$name, $this->isEventMuted($name), $this->isCustomEvent($name)]);
        // We nullify handler to prevent event from firing
        if ( $this->isEventMuted($name) ) $handler = null;
        
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
            if ( is_callable($handler) ) call_user_func($handler, $this);
        }
    }

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

    public function isCustomEvent(string $name) : bool
    {
        foreach ($this->dispatchesEvents as $class) {
            if ($class::__NAME__ === $name) return true;
        }

        return false;
    }

    public static function isEventsCapturingPrevented() : bool
    {
        return !!app()->config->database('preventEventsCapturing');
    }

    public static function preventEventsCapturing() : void
    {
        app()->config->set('database.preventEventsCapturing', true);
    }

    public static function allowEventsCapturing() : void
    {
        app()->config->set('database.preventEventsCapturing', false);
    }

    public function muteEvents(?array $name = null) : static
    {
        if ( !empty($name) ) {
            $this->mutedEvents += $name;
        } else {
            $this->eventsPrevented = true;
        }

        return $this;
    }

    public function isEventMuted(string $name) : bool
    {
        if ( $this->eventsPrevented ) return true;
        return in_array($name, $this->mutedEvents);
    }

    protected function booted() : void
    {
        /**
         * TODO
         */
    }

    /**
     * @return \ReflectionMethod[]
     */
    private function extractPublicMethods(object $obj) : array
    {
        $reflection = new \ReflectionClass($obj);
        return $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
    }

    private function getObserver(string $event) : ?array
    {
        foreach ($this->observers as $observer) {
            $methods = array_map(fn(\ReflectionMethod $method) => $method->name, $this->extractPublicMethods($observer));
            if (in_array($event, $methods)) {
                return [$observer, $event];
            }
        }
        return null;
    }
}