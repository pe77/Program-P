<?php

namespace Pe77\ProgramP\Classes;

use Pe77\ProgramP\Classes\Database\Connect;

class Human 
{
	protected $_programp;
	
	protected $_unique;
	protected $_type;
	
	private  $_props = array();
	
	function __construct($programp, $unique, $type) 
	{
		$this->_programp    = $programp;
        $this->_unique      = $unique;
        $this->_type        = $type;
        
		// Load all properties
		$this->LoadProp();
	}
	
	/**
	 * Return property by name
	 * @param string $name
	 */
	public function GetProp($name)
	{
		return array_key_exists($name, $this->_props) ? $this->_props[$name] : '';
	}
	
	
	/**
	 * Get unique key
	 */
	public function GetUnique()
	{
		return $this->_unique;
	}
	
	
	/**
	 * Create property, if exist, override
	 * @param string $name
	 * @param string $value
	 */
	public function SetProp($name, $value)
	{
		// save prop in db
		$this->_props[$name] = $value;
	}
	
	
	/**
	 * Load properties in DB 
	 */
	private function LoadProp()
	{
		// clear
		$this->_props = array();
		
		// load data
		$arrData = Connect::Fetch("SELECT * FROM `prop` WHERE `unique` = '{$this->_unique}' AND `type` = '{$this->_type}'");
		foreach ($arrData as $data)
			$this->_props[$data['name']] = $data['value'];
		//
	}
	
	/**
	 * Update prop in DB
	 */
	public function Save()
	{
		// clear all prop
		$this->ClearAllProp();
		
		// save all prop in DB
		foreach ($this->_props as $name=>$value)
			Connect::Query("INSERT `prop` VALUES ('{$this->_unique}', '{$this->_type}', '{$name}', '{$value}')");
		//
	}
	
	/**
	 * Delete all properties
	 */
	public function ClearAllProp()
	{
		// delete all prop in DB
		Connect::Query("DELETE from `prop` WHERE `unique` = '{$this->_unique}' AND `type` = '{$this->_type}'");
	}
	
	/**
	 * Delete property
	 */
	public function DelProp($name)
	{
		unset($this->_props[$name]);
	}
}