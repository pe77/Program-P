<?php

namespace Pe77\ProgramP\Classes;

class User extends Human 
{
	function __construct($unique) 
	{
		$this->_unique		= $unique; 
		$this->_type		= "user";
	}
}