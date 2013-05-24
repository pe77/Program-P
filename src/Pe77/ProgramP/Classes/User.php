<?php

namespace Pe77\ProgramP\Classes;

class User extends Human 
{
	public function __construct($programp, $unique)
    {	
        parent::__construct($programp, $unique, "user");
    }
}