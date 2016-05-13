<?php

namespace Pe77\ProgramP\Classes;

use Pe77\ProgramP\Classes\Database\Connect;

/**
 * Create a multple storage for "temp" data
 * @author Henrique
 */
class Data
{
	// save type
	public static $SAVETYPE_COOKIE 		= "cookie";
	public static $SAVETYPE_SESSION 	= "session";
	public static $SAVETYPE_DATABASE 	= "database";
	
	private $_saveType;
	private $_data;
	private $_user;
	
	
	/**
	 * Set a save type
	 * @param Data::$SAVETYPE_COOKIE | Data::$SAVETYPE_SESSION | Data::$SAVETYPE_DATABASE $saveType
	 * @param \User $user - User to which information will be associated
	 */
	function __construct($saveType, $user)
	{
		$this->_saveType = $saveType;
		$this->_user = $user;
	}
	
	/**
	 * Return all data for unique key acess
	 * @return array - data
	 */
	public function Load()
	{
		if($this->_data)
			return $this->_data;
		//
		
		switch ($this->_saveType) 
		{
			case Data::$SAVETYPE_COOKIE:
				$this->_data = isset($_COOKIE['ppdata']) ? unserialize($_COOKIE['ppdata']) : array();
			break;
			
			case Data::$SAVETYPE_SESSION:
				$sid = session_id();
		
				if(empty($sid))
					session_start();
				//
				$this->_data = isset($_SESSION['ppdata']) ? $_SESSION['ppdata'] : array();
			break;
			
			case Data::$SAVETYPE_DATABASE:
				$this->_data = $this->LoadDB();
			break;
		}
		
		return $this->_data;
	}
	
	/**
	 * Save data in COOKIE | SESSION | DATABASE
	 * @param array $data
	 */
	public function Save($data) 
	{
		switch ($this->_saveType) 
		{
			case Data::$SAVETYPE_COOKIE:
				setcookie('ppdata', serialize($data));
			break;
			
			case Data::$SAVETYPE_SESSION:
				$sid = session_id();
		
				if(empty($sid))
					session_start();
				//
				
				$_SESSION['ppdata'] = $data;
			break;
			
			case Data::$SAVETYPE_DATABASE:
				$this->SaveDB($data);
			break;
		}
		
		$this->_data = $data;
	}
	
	/**
	 * Clear all data storage for unique key
	 */
	public function Clear()
	{
		switch ($this->_saveType) 
		{
			case Data::$SAVETYPE_COOKIE:
				setcookie('ppdata', '');
			break;
			
			case Data::$SAVETYPE_SESSION:
				session_start();
				unset($_SESSION['ppdata']);
			break;
			
			case Data::$SAVETYPE_DATABASE:
				$this->ClearDB();
			break;
		}
		
		$this->_data = array();
	}
	
	/**
	 * Delete all data for unique key 
	 */
	private function ClearDB()
	{
		$unique = $this->GetUniqueDB();
		
		Connect::Query("
			DELETE FROM 
				data
			WHERE
				`unique` = '{$unique}'
		");
	}
	
	/**
	 * Save serializable data in Database
	 * @param array $data
	 */
	private function SaveDB($data)
	{
		$serializeData = serialize($data);
		$unique = $this->GetUniqueDB();
		
		Connect::Query("
			INSERT INTO 
				data
			(`unique`, `data`)
			VALUES
			('{$unique}', '{$serializeData}')
			ON DUPLICATE KEY 
				UPDATE 
					`unique`= VALUES(`unique`), 
					`data`	= VALUES(`data`)
		");
	}
	
	/**
	 * Load data storage in database
	 * @return array - unserialize data:
	 */
	private function LoadDB() 
	{
		$unique = $this->GetUniqueDB();
		
		if($data = Connect::GetOne("SELECT * FROM `data` WHERE `unique` = '$unique'"))
		{
			return unserialize($data['data']);
		}else{
			return array();
		}
	}
	
	
	/**
	 * Generate a unique key for assossiation save
	 * @return string - unique key
	 */
	public function GetUniqueDB()
	{
		return $this->_user->GetUnique();
	}
}