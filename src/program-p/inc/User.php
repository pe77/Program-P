<?php

require 'Human.php';

class User extends Human 
{
	function __construct($unique) 
	{
		$this->_unique		= $unique; 
		$this->_type		= "user";
	}
}