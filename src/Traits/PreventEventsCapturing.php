<?php 
namespace Clicalmani\Database\Traits;

trait PreventEventsCapturing 
{
    public function __construct()
    {
        \Clicalmani\Database\Factory\Models\Elegant::preventEventsCapturing();
    }
}
