<?php
namespace Clicalmani\Database\Exceptions;

/**
 * Class DBQueryException
 * 
 * @package Clicalmani\Database
 * @author @clicalmani
 */
class MassAssignmentException extends \RuntimeException {
	public function __construct(string $message, int $code = 0, ?\Throwable $previous = null){
		parent::__construct($message, $code, $previous);
	}
}