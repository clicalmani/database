<?php 
namespace Clicalmani\Database\Traits;

trait HasFactory
{
    public static function seed() : \Clicalmani\Database\Factory\FactoryInterface
    {
        $className = get_called_class();
        $model = substr($className, strrpos($className, "\\") + 1);
        
        $factory = "\\Database\\Factories\\" . $model . 'Factory';
        
        return $factory::new();
    }
}
