<?php

namespace Pe77\ProgramP\Classes;

use Pe77\ProgramP\Classes\Data;

class Parser 
{
	static private $_domDoc;
	static private $_domXPath;
	
	static private $_input;
	static private $_response;
	
	static private $_data;
	static private $_dataStorage;
	
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
		
		// create a storage data instance
		self::$_dataStorage = new Data(Data::$SAVETYPE_DATABASE);
		self::$_data = self::$_dataStorage->Load();
		
		// self::$_data['topics'] = array('chan', 'cumprimento', 'agressivo');
		
		// set default
		if(!isset(self::$_data['topics']))
			self::$_data['topics'] = array();
		//
		
		// response object
		self::$_response = new Response();
		
		// set response
		self::$_response->SetResponse(self::Find($input));
		
		// add topics
		foreach (self::$_data['topics'] as $topicName)
			self::$_response->AddTopic($topicName);
		//
		
		// save temp data
		self::$_dataStorage->Save(self::$_data);
		
		// self::$_dataStorage->Clear();
		
		// return response
		return self::$_response;
	}
	
	static private function Find($input)
	{
		// set user input
		self::SetInput($input);
		
		$xpathQuery = '//aiml';
		
		// if topic exist, try search inside topic before
		if(count(self::$_data['topics']) > 0)
		{
			// mount query string
			$xpathQuery = "/";
			foreach (self::$_data['topics'] as $topic)
				$xpathQuery .= "/topic[@name='" . $topic . "']";
			//
		}
		
		// pass the aiml category list
		if($categories = self::$_domXPath->query($xpathQuery)->item(0))
			// find corresponding category
			if($category = self::SearchCategory($categories))
				// pre-process template tag and set response
				return self::ProcessTemplate(
						self::GetAllTagsByName($category, 'template', true)
					);
			//
		//
					
		return '';
	}
	
	static private function ProcessTemplate($template)
	{
		// compile srai
		self::CompileSrai($template);
		
		// compile random
		self::CompileRandom($template);
		
		return (string)$template->nodeValue;
	}
	
	static private function CompileRandom($domNode)
	{
		if($randomNodes = self::GetAllTagsByName($domNode, 'random'))
		{
			foreach ($randomNodes as $rNode) 
			{
				// check if li tag exist 
				if($liNodes = self::GetAllTagsByName($rNode, 'li'))
				{
					$lis = array();
					
					foreach ($liNodes as $lNode) 
						$lis[] = $lNode;
					//
					
					// select li node
					$selectedLi = $lis[array_rand($lis, 1)];
					
					// remove all others node from random
					foreach ($lis as $lnode) 
						if(!$lnode->isSameNode($selectedLi))
							$rNode->removeChild($lnode);
					//
					
					// change random node for selectedLi value
					$domNode->replaceChild(
						self::$_domDoc->createTextNode(self::ProcessTemplate($selectedLi)),
						$rNode
					);
					
				}
			}
		}
	}
	
	static private function CompileSrai($node)
	{
		if($srais = self::GetAllTagsByName($node, 'srai'))
		{
			foreach ($srais as $srai) 
			{
				// re-find another response for srai and replace
				$newNode = self::$_domDoc->createTextNode(
					self::Find(
						self::ProcessTemplate($srai)));
				
				$node->replaceChild($newNode, $srai);
			}
		}
	}
	
	/**
	 * Get valid category for input
	 * @param DOMElement
	 * @return Category|False 
	 */
	static private function SearchCategory($domNode, $reverse = false)
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
					// set topic
					$domNode->nodeName == 'topic' ? self::SetTopic($domNode) : self::SetDefaultTopic(); 
					
					return $category;
				}
			}
		}
		//
		
		// check inside topics
		if(!$reverse)
			if($category = self::SearchTopic($domNode))
				return $category;
		//
		
		// revert search in topics
		if($category = self::SearchTopicReverse($domNode))
			return $category;
		//
		
		return false;
	}
	
	static private function SearchTopicReverse($domNode)
	{
		// Get prev node
		$prevNode = $domNode->parentNode;
		
		// check category
		if($category = self::SearchCategory($prevNode, true))
			return $category;
		
		
		return false;
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
		self::$_data['topics'] = array();
	}
	
	static private function SetInput($input)
	{
		self::$_input = $input;
	}
	
	static private function SetTopic($node)
	{
		self::$_data['topics'] = array_reverse(self::GetTopicTree($node, array()));
	}
	
	static private function GetTopicTree($node, $arrTopics)
	{
		if($node->nodeName != 'topic')
			return $arrTopics;
		//
		
		$arrTopics[] = $node->getAttribute('name');
		
		return self::GetTopicTree($node->parentNode, $arrTopics);
	}
	
	/**
	 * Search in node by tag
	 * @param DOMElement $domNode
	 * @param string $tagName - xpath model
	 * @param boolean $getOne - if true, get only one element, not array ([0])
	 * @return array<DOMElement>|DOMElement|False
	 */
	static private function GetAllTagsByName($domNode, $tagName, $getOne = false)
	{
		$arrResponse = array();
		
		foreach(self::$_domXPath->query($tagName, $domNode) as $node)
			$arrResponse[] = $node;
		//
		
		return count($arrResponse) > 0 ? $getOne ? $arrResponse[0] : $arrResponse : false;
	}
}