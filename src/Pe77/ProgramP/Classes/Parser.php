<?php

namespace Pe77\ProgramP\Classes;

class Parser 
{
	static private $_domDoc;
	static private $_domXPath;
	
	static private $_topicName = '';
	static private $_topicObj = null;
	
	static private $_input;
	static private $_response;
	
	/**
     * Get user question, parse and response
     * @param \User $user - User who is asking
     * @param \Bot $bot - bot are replying
     * @param string $input - User input
     * @return string - response
     */
	static function Parse($user, $bot, $input)
	{
		// create and load xml handler
		self::$_domDoc = new \DOMDocument();
		self::$_domDoc->loadXML($bot->aimlString());
		self::$_domXPath = new \DomXPath(self::$_domDoc);
		
		// set user input
		self::SetInput($input);
		
		// response object
		self::$_response = new Response();
		
		
		// pass the aiml category list
		if($categories = self::$_domXPath->query('//aiml')->item(0))
			// find corresponding category
			if($category = self::SearchCategory($categories))
				// pre-process template tag and set response
				self::$_response->SetResponse(
					self::ProcessTemplate(
						self::GetAllTagsByName($category, 'template')
					)
				);
			//
		//
		
		return self::$_response;
	}
	
	static private function ProcessTemplate($template)
	{
		$template = $template[0];
		
		return (string)$template[0]->nodeValue;
	}
	
	/**
	 * Get valid category for input
	 * @param DOMElement
	 * @return Category|False 
	 */
	static private function SearchCategory($domNode)
	{		
		
		
		// check if categories exist
		if(!$categories = self::GetAllTagsByName($domNode, 'category'))
			return false;
		
		// check if any pattern in default patterns
		foreach ($categories as $category)
		{
			foreach (self::GetAllTagsByName($category, 'pattern') as $pattern)
			{
				if(self::CheckPattern($pattern->nodeValue))
				{
					self::SetDefaultTopic();
					return $category;
				}				
			}
		}
		//
		
		// check inside topics
		return self::SearchTopic($domNode); 
	}
	
	/**
	 * Get valid category for input inside any topic
	 * @param DOMElement
	 * @return Category|False 
	 */
	static private function SearchTopic($domNode)
	{
		// if no exist topics, return false
		if(!$topics = self::GetAllTagsByName($domNode, 'topic'))
			return false;
		//
		
			
		
		// check each topic looking for another valid category 
		foreach ($topics as $topic)
		{
			if($category = self::SearchCategory($topic))
			{
				self::SetTopic($category->getAttribute('name'));
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
	
	/**
	 * Search in node by tag
	 * @param DOMElement $domNode
	 * @param string $tagName
	 * @param boolean $getOne - if true, get only one element, not array ([0])
	 * @return array<DOMElement>|DOMElement|False
	 */
	static private function GetAllTagsByName($domNode, $tagName, $getOne = false)
	{
		if(!$domNode->hasChildNodes())
			return false;
		//
		
		$arrResponse = array();
		
		foreach ($domNode->childNodes as $child)
			if($child->nodeName == $tagName)
				$arrResponse[] = $child; 
			//
		//
		
		return count($arrResponse) > 0 ? $getOne ? $arrResponse[0] : $arrResponse : false;
	}
}