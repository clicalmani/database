<?php
namespace Clicalmani\Database\Events;

interface EventListenerInterface
{
    /**
     * Register event
     * 
     * @param mixed $handler Event handler
     * @return void
     * @throws \RuntimeException
     */
    public function register(mixed $handler) : void;

    /**
     * Handle event
     * 
     * @param mixed $data
     * @return void
     */
    public function handle(mixed $data) : void;
}