<?php

namespace Pe77\ProgramP\Classes;

use Pe77\ProgramP\Classes\Data;

class Parser 
{
	static private $_user;
	static private $_bot;
	
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
	static function Parse(User $user, Bot $bot, $input)
	{
		// create and load xml handler
		self::$_domDoc = new \DOMDocument();
		self::$_domDoc->loadXML($bot->aimlString());
		self::$_domXPath = new \DomXPath(self::$_domDoc);
		
		// set bot and user
		self::$_user = $user;
		self::$_bot = $bot;
		
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
		
		// compile think
		self::CompileThink($template);
		
		// compile get
		self::CompileGet($template);
		
		// compile template
		self::CompileCondition($template);
		
		return (string)$template->nodeValue;
	}
	
	static private function CompileThink($node)
	{
		if($thinkNodes = self::GetAllTagsByName($node, 'think'))
		{
			foreach ($thinkNodes as $think)
			{
				// compile set
				self::CompileSet($think);
				
				// compile get
				self::CompileGet($think);
				
				// remove think node
				$node->removeChild($think);
			}
		}
	}
	
	static private function CompileSet($node)
	{
		if($sets = self::GetAllTagsByName($node, 'set'))
		{
			foreach ($sets as $setNode)
			{
				$name = false;
				$value = false;
				
				// check for 2.0 model
				if($name = self::GetAllTagsByName($setNode, 'name', true))
					$name = $name->nodeValue;
				//
				
				$value = self::GetAllTagsByName($setNode, 'value', true);
				
				// check for name in old aiml model
				if($setNode->getAttribute('name') != '')
				{
					$name = $setNode->getAttribute('name');
					$value = $setNode; 
				}
				
				// parse or re-parse value
				if($value)
					$value = self::ProcessTemplate($value);
				//
				
				// save data for user
				if($name && $value)
				{
					self::$_user->SetProp($name, $value);
					self::$_user->Save();
				}
				
				// remove node
				$node->removeChild($setNode);
			}
		}
		
		
	}
	
	static private function CompileGet($node)
	{
		if($gets = self::GetAllTagsByName($node, 'get'))
		{
			foreach ($gets as $getNode)
			{
				$name = false;
				$value = '';
		
				// check for another tags
				if($name = self::GetAllTagsByName($getNode, 'name', true))
				{
					$value = self::$_domDoc->createTextNode(
									self::ProcessTemplate($name)
							   );
			    	
				}else{
					// check for name in old aiml model
					if($node->getAttribute('name') != '')
						$value = self::$_domDoc->createTextNode($node->getAttribute('name'));
					//
				}
				
				// load value from db
				$value = self::$_domDoc->createTextNode(
					self::$_user->GetProp($value->nodeValue));
				
				// replace child for the value
				$node->replaceChild($value, $getNode);
			}
		}
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
	
	static private function CompileCondition($node)
	{
		// search condition tag
		if($conditions = self::GetAllTagsByName($node, 'condition'))
		{
			foreach ($conditions as $conditionNode)
			{
				$varValue = $pass = false;
				
				// simple condition, 1.1 aiml style
				if($conditionNode->getAttribute('name') != '')
				{
					$varValue = $name = self::$_user->GetProp($conditionNode->getAttribute('name')); 
					
					if($name == $conditionNode->getAttribute('value'))
						$pass = true;
					//
				}
				
				// simple condition for 2.0 aiml
				if($name = self::GetAllTagsByName($conditionNode, 'name', true))
				{
					// get var value
					$varValue = self::$_user->GetProp($name->nodeValue);
					
					if($value = self::GetAllTagsByName($conditionNode, 'value', true))
					{
						// hold instance
						$tmp_value = $value;
						
						// pre-process value
						$value = self::$_domDoc->createTextNode(
							self::ProcessTemplate($value)
						);
						
						// check flag
						$pass =  $varValue == $value->nodeValue;
						
						// remove nodes
						$conditionNode->removeChild($name);
						$conditionNode->removeChild($tmp_value);
					}
				}
				
				// check for <li> conditional type tag
				if($varValue && $lis = self::GetAllTagsByName($conditionNode, 'li'))
				{
					foreach ($lis as $liNode)
					{
						// check li value 1.1 aiml
						if($liNode->getAttribute('value') != '')
						{
							if(
								$varValue == $liNode->getAttribute('value')
								||
									(
										$liNode->getAttribute('value') == 'true'
										&&
										$varValue != ''
									)
								)
							{
								// replace <li>conditional by value
								$newNode = self::$_domDoc->createTextNode(
									self::ProcessTemplate($liNode)
								);
								
								// replace child for the value
								$conditionNode->replaceChild($newNode, $liNode);
								
								// set flag
								$pass = true;
							}else{
								// remove li
								$conditionNode->removeChild($liNode);
							}
						}
						
						// check li value 2.0 aiml
						if($value = self::GetAllTagsByName($liNode, 'value', true))
						{
							// set flag
							if(
								$varValue == $value->nodeValue 
								|| 
								(
									$varValue != ''
									&&
									$value->nodeValue == 'true'
								)
							)
							{
								// hold value
								$tmp_value = $value;
								
								// remove value
								$liNode->removeChild($value); 
								 
								// pre-process value
								$newNode = self::$_domDoc->createTextNode(
									self::ProcessTemplate($liNode)
								);
								
								// replace child for the value
								$conditionNode->replaceChild($newNode, $liNode);
								
								$pass = true;
								
							}else{
								// remove
								$conditionNode->removeChild($liNode); 
							}
							
							
						}
					}
				}
				
				if($pass)
				{					
					// replace conditional by value
					$newNode = self::$_domDoc->createTextNode(
						self::ProcessTemplate($conditionNode)
					);
					
					// replace child for the value
					$node->replaceChild($newNode, $conditionNode);
					
				}else{
					// remove conditional
					$node->removeChild($conditionNode);
				}
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