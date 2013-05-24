<?php

namespace Pe77\ProgramP;

use Pe77\ProgramP\Classes\Bot;
use Pe77\ProgramP\Classes\Database\Connect; 

class ProgramP 
{	
	var $_aimlFile;
	var $_bot;
	var $_user;
	
	static $config;
	
	function __construct($config) 
	{
		self::$config = $config;
		
		Connect::init(self::$config['db']);
	}
	
	/**
	 * Get user question, parse and response
	 * @param User $user - User who is asking
	 * @param Bot $bot - bot are replying
	 * @param string $question - User input
	 */
	function GetResponse($user, $bot, $question)
	{
		
	}
	
	
	/**
	 * Load Bot
	 * @param string $botName - unique key for identific bot, and bot aiml file to. 
	 * @return Bot
	 */
	function GetBot($unique)
	{
		
		$fileFullName = ProgramP::$config['aiml']['dir'] . '/' . $unique.'.aiml';
		
		
		if(file_exists($fileFullName))
		{
			// read aiml file
			$fh 		= fopen($fileFullName, 'r');
			$aimlString = fread($fh, filesize($fileFullName));
			fclose($fh);
			
		}else{
			throw new \Exception("File AIML not found in : " . $fileFullName);
		}
		
		// @todo check aiml format
		
		// return bot
		return new Bot($unique, $aimlString);
	}
	
	
	/**
	 * Load user by unique key (name+IP exemple)
	 * @param string $userUnique
	 */
	function GetUser($userUnique)
	{
		// check if user exist
		
		// if exist, load
		
		// else, create user
		
		// return user
	}
}
