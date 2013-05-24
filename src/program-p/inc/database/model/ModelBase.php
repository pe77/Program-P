<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../Connect.php';

class ModelBase {
	
	protected $sqlBaseLoad = '';
	
	protected $id;
	
	/**
	 * GET
	 * @return int ID da playlist
	 */
	public function getId()
	{
		
		return $this->id;
	}
	
	function __construct($data) 
	{
		if(is_numeric($data))
		{
			
			$this->id = $data;
			$this->sqlBaseLoad = str_replace("%id%", $this->id, $this->sqlBaseLoad);
			$data = Connect::GetOne($this->sqlBaseLoad);
		}
		//
		
		
		$this->LoadData($data);
	}
	
	protected function LoadData($data){}
}