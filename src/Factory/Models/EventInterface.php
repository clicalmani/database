<?php
namespace Clicalmani\Database\Factory\Models;

interface EventInterface
{
    /**
     * Check event
     * 
     * @param string $name
     * @return bool
     */
    public function isEvent(string $name) : bool;

    public function isCustomEvent(string $name) : bool;

    /**
     * Check if events capturing is prevented
     * 
     * @return bool
     */
    public static function isEventsCapturingPrevented() : bool;

    /**
     * Prevent events capturing
     * 
     * @return void
     */
    public static function preventEventsCapturing() : void;

    /**
     * Allow events capturing
     * 
     * @return void
     */
    public static function allowEventsCapturing() : void;

    /**
     * Mute events
     * 
     * @param array|null $name
     * @return \Clicalmani\Database\Factory\Models\ModelInterface
     */
    public function muteEvents(?array $name = null) : \Clicalmani\Database\Factory\Models\ModelInterface;

    /**
     * Check if event is muted
     * 
     * @param string|array|null $name
     * @return static
     */
    public function isEventMuted(string $name) : bool;
}