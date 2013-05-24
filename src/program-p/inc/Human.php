<?php

class Human 
{
	var $_unique;
	var $_name;
	var $_type;
	
	function Human() 
	{
		
	}
	
	/**
	 * Return property by name
	 * @param string $name
	 */
	function GetProp($name)
	{
		
	}
	
	
	/**
	 * Create property, if exist, override
	 * @param string $name
	 * @param string $value
	 */
	function SetProp($name, $value)
	{
		
	}
	
	
	/**
	 * Load property 
	 */
	function LoadProp()
	{
		// check if exist by unique, load
		
		// else, create 
		
		// return instance
	}
	
	/**
	 * Update prop in DB
	 */
	function Save()
	{
		// clear all prop
		$this->ClearProp();
		
		// save all prop in DB
	}
	
	/**
	 * Delete, delete, delete!
	 */
	function Delete()
	{
		
	}
	
	/**
	 * Delete All properties
	 */
	function ClearProp()
	{
		// delete all prop in DB
	}
}