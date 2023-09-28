<?php
namespace Clicalmani\Database\Exceptions;

class DataTypeException extends \Exception {
	function __construct($message){
		parent::__construct($message);
	}
}
