<?php
namespace Clicalmani\Database\Exceptions;

class DBQueryException extends \PDOException {
	function __construct($message){
		parent::__construct($message);
	}
}
