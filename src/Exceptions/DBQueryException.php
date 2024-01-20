<?php
namespace Clicalmani\Database\Exceptions;

/**
 * Class DBQueryException
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class DBQueryException extends \PDOException {
	public function __construct(string $message){
		parent::__construct($message);
	}
}
