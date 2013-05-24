<?php

namespace Pe77\ProgramP\Classes;

class Human 
{
	var $_unique;
	var $_name;
	var $_type;
	
	var $_props = array();
	
	function Human() 
	{
		
	}
	
	/**
	 * Return property by name
	 * @param string $name
	 */
	function GetProp($name)
	{
		return key_exists($name, $this->_props) ? $this->_props[$name] : '';
	}
	
	
	/**
	 * Create property, if exist, override
	 * @param string $name
	 * @param string $value
	 */
	function SetProp($name, $value)
	{
		// save prop in db
		$this->_props[$name] = $value; 
		
	}
	
	
	/**
	 * Load property 
	 */
	function LoadProp()
	{
		
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