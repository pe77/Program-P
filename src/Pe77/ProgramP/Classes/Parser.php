<?php

namespace Pe77\ProgramP\Classes;

class Parser 
{
	static private $_obj;
	
	static private $_topicName = '';
	static private $_topicObj = null;
	
	static private $_input;
	static private $_response;
	
	/**
     * Get user question, parse and response
     * @param User $user - User who is asking
     * @param Bot $bot - bot are replying
     * @param string $input - User input
     * @return string - response
     */
	static function Parse($user, $bot, $input)
	{
		self::$_obj = simplexml_load_string($bot->aimlString());
		
		self::SetInput($input);
		/*
		echo '<pre>' . print_r(self::$_obj, true);
		die();
		// */
		
		// response object
		self::$_response = new Response();
		
		if($category = self::SearchCategory(self::$_obj))
			self::$_response->SetResponse($category->template);
		//
		
		return self::$_response;
	}
	
	/**
	 * Get valid category for input
	 * @param aiml simpleXML parse $obj
	 * @return Category|False 
	 */
	static private function SearchCategory($obj)
	{
		// check pattern in default patterns
		foreach ($obj->category as $category)
		{
			if(self::CheckPattern($category->pattern))
			{
				self::SetDefaultTopic();
				return $category;
			}
		}
		//
		
		// check inside topics
		return self::SearchTopic($obj); 
	}
	
	/**
	 * Get valid category for input inside any topic
	 * @param aiml simpleXML parse $obj
	 * @return Category|False 
	 */
	static private function SearchTopic($obj)
	{
		// if no exist topics, return false
		if(!array_key_exists('topic', $obj))
			return false;
		//
		
		// check each topic looking for another valid category 
		foreach ($obj->topic as $topicObj)
		{
			if($category = self::SearchCategory($topicObj))
			{
				self::SetTopic((string)$topicObj['name']);
				return $category;
			}
		} 
		//	
		
		
		return false;
	}
	
	/**
	 * Check if pattern in category node is ok
	 * @return boolean - if ok or not
	 */
	static private function CheckPattern($pattern)
	{
		return strtolower(self::$_input) == strtolower($pattern);
	}
	
	static private function SetDefaultTopic()
	{
		self::$_topicName = '';
		self::$_topicObj = null;
	}
	
	static private function SetInput($input)
	{
		self::$_input = $input;
	}
	
	static private function SetTopic($topicName)
	{
		self::$_response->AddTopic($topicName);
		self::$_topicName = $topicName;
	}
}