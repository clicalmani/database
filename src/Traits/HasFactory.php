<?php 
namespace Clicalmani\Database\Traits;

trait HasFactory
{
    public static function seed() : \Clicalmani\Database\Factory\FactoryInterface
    {
        $className = get_called_class();
        
        $rootNamespace = "\\Database\\Factories\\";

        if (str_starts_with($className, $rootNamespace)) {
            $relativeClass = substr($className, strlen($rootNamespace));
        } else {
            $relativeClass = substr($className, strrpos($className, "\\") + 1);
        }

        $factoryClass = $rootNamespace . $relativeClass . 'Factory';

        if (!class_exists($factoryClass)) {
            throw new \RuntimeException("Factory [{$factoryClass}] does not exits for the model [{$className}].");
        }
        
        return $factoryClass::new();
    }
}
