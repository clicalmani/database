<?php
namespace Clicalmani\Database\Exceptions;

/**
 * Class DataTypeException
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class DataTypeException extends \Exception {
	public function __construct(string $message)
	{
		parent::__construct($message);
	}
}
