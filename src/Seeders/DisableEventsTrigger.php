<?php 
namespace Clicalmani\Database\Seeders;

trait DisableEventsTrigger 
{
    public function __construct()
    {
        \Clicalmani\Database\Factory\Models\Model::$triggerEvents = false;
    }
}
