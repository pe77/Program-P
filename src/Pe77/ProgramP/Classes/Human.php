<?php

namespace Pe77\ProgramP\Classes;

use Pe77\ProgramP\Classes\Database\Connect;

class Human 
{
	var $_unique;
	var $_name;
	var $_type;
	
	var $_props = array();
	
	function __construct() 
	{
		// Load all properties
		$this->LoadProp();
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
	 * Load properties in DB 
	 */
	function LoadProp()
	{
		$arrData = Connect::Fetch("SELECT * FROM prop WHERE unique = '{$this->_unique}' AND type = '{$this->_type}'");
		foreach ($arrData as $data)
			$this->_props[$data['name']] = $data['value'];
		//
	}
	
	/**
	 * Update prop in DB
	 */
	function Save()
	{
		// clear all prop
		$this->ClearProp();
		
		// save all prop in DB
		foreach ($this->_props as $name=>$value)
			Connect::Query("INSERT prop SET ('{$this->_unique}', {'{$this->_type}'}, {{$name}}, '{$value}')");
		//
	}
	
	
	/**
	 * Delete All properties
	 */
	function ClearProp()
	{
		// delete all prop in DB
		Connect::Query("DELETE from prop WHERE unique = '{$this->_unique}' AND type = '{$this->_type}'");
	}
}