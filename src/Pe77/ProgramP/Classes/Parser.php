<?php

namespace Pe77\ProgramP\Classes;

use Pe77\ProgramP\Classes\Data;
use Pe77\ProgramP\Classes\Utils\Math;

class Parser 
{
	static private $_user;
	static private $_bot;
	
	static private $_config;
	
	static private $_domDoc;
	static private $_domXPath;
	
	static private $_input;
	static private $_response;
	static private $_star;
	
	static private $_data;
	static private $_dataStorage;


	static private $_responseData;

	static function GetResponseData()
	{
		return self::$_responseData;
	}

	static function SetConfig($config)
	{
		return self::$_config = $config;
	}
	
	/**
     * Get user question, parse and response
     * @param \User $user - User who is asking
     * @param \Bot $bot - bot are replying
     * @param string $input - User input
     * @return string - response
     */
	static function Parse(User $user, Bot $bot, $input)
	{
		/*
		$x = preg_match('/^(.*)voce tem irma(.*)$/i', "oi p voce tem irma");
		print_r($x);
		die();
		*/
		
		// create and load xml handler
		self::$_domDoc = new \DOMDocument();
		self::$_domDoc->preserveWhiteSpace = false;
		self::$_domDoc->loadXML($bot->aimlString());
		self::$_domXPath = new \DomXPath(self::$_domDoc);

		// includes
		self::$_domDoc = self::IncludeMerge(self::$_domDoc);

		// set bot and user
		self::$_user = $user;
		self::$_bot = $bot;
		
		// preparse input
		$input = self::PreParseInput($input);
		
		// create a storage data instance
		self::$_dataStorage = new Data(Data::$SAVETYPE_DATABASE, $user);
		self::$_data = self::$_dataStorage->Load();
		
		// set default topics
		if(!isset(self::$_data['topics']))
			self::$_data['topics'] = array();
		//
		
		// set default that
		if(!isset(self::$_data['that']))
			self::$_data['that'] = array();
		//
		
		// echo '[' . self::GetLastThat() . ']';
		// set default that
		if(!isset(self::$_data['input']))
			self::$_data['input'] = array();
		//
			
		self::$_star = array();
		
		// response object
		self::$_response = new Response();
		
		// get response
		$response = self::Find($input);
		
		// add topics
		foreach (self::$_data['topics'] as $topicName)
			self::$_response->AddTopic($topicName);
		//
		
		// self::$_dataStorage->Clear();
		
		// if response is '', looking for default tag
		$response = $response == '' ? self::GetDafault() : $response;
		
		// set response
		self::$_response->SetResponse($response);
		
		// set response for 'that'
		self::SetResponse($response);
		
		// save temp data
		self::$_dataStorage->Save(self::$_data);

		// mostra os operadores matematicos novamente
		self::$_response = self::ReturnMathematicalOperators(self::$_response);
		
		// return response
		return self::$_response;
	}

	// check if have any 'include' tag for mixin propose
	static private function IncludeMerge($domDoc)
	{
		$xpathQuery = '//include';

		$includeDomDocs = array();

		// get inlude nodes
		foreach(self::$_domXPath->query($xpathQuery, $domDoc) as $includeNode)
		{
			if($includeNode->hasAttributes())
			{
				foreach ($includeNode->attributes as $attr)
				{
					switch ($attr->nodeName) {
						case 'file':

							$fileFullName = self::$_config['aiml']['dir'] . '/' . $attr->nodeValue;

							// check if the file exists
					        if(!file_exists($fileFullName))
					            throw new \Exception("Include file AIML not found in : " . $fileFullName);
					        //

					        // read aiml file
        					$aimlString = file_get_contents($fileFullName);

        					// create domdoc
        					$includeDoc = new \DOMDocument;
        					$includeDoc->loadXML($aimlString);

        					// save
        					$includeDomDocs[] = $includeDoc;

							break;
					}
				}
			}
		}


		// import all include docs
		foreach ($includeDomDocs as $includeDoc)
		{
			// load topcs, category(with subtags too)
			$includeTags = array('category', 'topics');
			$domXPath = new \DomXPath($includeDoc);

			foreach ($includeTags as $tag)
			{
				foreach($domXPath->query('//' . $tag, $includeDoc) as $node)
				{
					// import node
					$node = $domDoc->importNode($node, true);
					$domDoc->documentElement->appendChild($node);
				}
			}
		}

		return $domDoc;
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
	
	static private function GetDafault()
	{
		$xpathQuery = '//default';
		
		// pass the aiml category list
		if($default = self::$_domXPath->query($xpathQuery)->item(0))
				// pre-process default tag and set response
				return self::ProcessTemplate(
						$default
					);
			//
		//
		
		return '';
	} 
	
	static private function ProcessTemplate($template)
	{
		// compile think
		self::CompileThink($template);
		
		// compile srai
		self::CompileSrai($template);
		
		// compile random
		self::CompileRandom($template);
		
		// compile input 
		self::CompileInput($template);
		
		// compile star pattern
		// self::CompileStar($template);
		
		// compile set
		self::CompileSet($template);

		// compile get
		self::CompileGet($template);

		// compile bot
		self::CompileBot($template);
		
		// compile condition
		self::CompileCondition($template);
		
		// compile lowercase
		self::CompileLowercase($template);
		
		// compile uppercase
		self::CompileUppercase($template);

		// compile count
		self::CompileCount($template);

		// compile del
		self::CompileDel($template);

		// compile reponse data | <data>
		self::CompileResponseData($template);
		
		
		return (string)$template->nodeValue;
	}
	
	static private function CompileStar($templateNode)
	{
		
		// search for star
		if($stars = self::GetAllTagsByName($templateNode, './/star'))
		{
			foreach ($stars as $starNode)
			{
				$value = ''; 
				$index = 0;
				
				// get index
				if($starNode->getAttribute('index') != '')
				{
					$index = intval($starNode->getAttribute('index'));
					$index--;
				} 
				
				// get value
				if(self::GetStar($index) !== false)
					$value = self::GetStar($index); 
				// 
				
				// replace child for the value
				$starNode->parentNode->replaceChild(self::$_domDoc->createTextNode($value), $starNode);
			}
		}
	} 

	static private function CompileResponseData($node)
	{
		// check data tag
		if($tags = self::GetAllTagsByName($node, 'data'))
		{
			foreach ($tags as $tag)
			{
				$data = array();

				// loop all values 
				foreach ($tag->childNodes as $dataValueTag)
				{
					if($dataValueTag->hasAttributes())
					{
						$data[$dataValueTag->nodeName]['value'] = self::ProcessTemplate($dataValueTag);

						foreach ($dataValueTag->attributes as $attr)
							$data[$dataValueTag->nodeName][$attr->nodeName] = $attr->nodeValue;
						//
					}else{
						$data[$dataValueTag->nodeName] = self::ProcessTemplate($dataValueTag);
					}
				}

				// ProcessTemplate

				// node to string
				// $xmlStr = $tag->C14N();

				// transform xml string raw data = array
				// $data = json_decode(json_encode((array)simplexml_load_string($xmlStr)),1);
				// $data = array_map('trim',$data); // clear spaces


				self::$_responseData[] = $data;
				
				

				// clear node, get data only by method
				$node->removeChild($tag);
			}
		}
	}
	
	static private function CompileLowercase($node)
	{
		// check lowercase tag
		if($lowers = self::GetAllTagsByName($node, 'lowercase'))
		{
			foreach ($lowers as $lowerTag)
			{
				// die(strtolower(self::ProcessTemplate($lowerTag)));
				
				// load value from db
				$newNode = self::$_domDoc->createTextNode(
						strtolower(
							self::ProcessTemplate($lowerTag)
							)
					);
				
				// replace child for the value
				$node->replaceChild($newNode, $lowerTag);
			}
		}
	}
	
	static private function CompileUppercase($node)
	{
		// check lowercase tag
		if($uppers = self::GetAllTagsByName($node, 'uppercase'))
		{
			foreach ($uppers as $upperTag)
			{
				// die(strtolower(self::ProcessTemplate($lowerTag)));
				
				// load value from db
				$newNode = self::$_domDoc->createTextNode(
						strtoupper(
							self::ProcessTemplate($upperTag)
							)
					);
				
				// replace child for the value
				$node->replaceChild($newNode, $upperTag);
			}
		}
	}


	static private function CompileCount($node)
	{
		// check count tag
		if($counts = self::GetAllTagsByName($node, 'count'))
		{
			foreach ($counts as $countTag)
			{

				// process another nodes
				$val = self::ProcessTemplate($countTag);

				// calc
				$val = Math::calculate(self::ReturnMathematicalOperators($val));

				// create new note
				$newNode = self::$_domDoc->createTextNode($val);

				// replace child for the value
				$node->replaceChild($newNode, $countTag);
			}
		}
	}
	
	static private function CompileThink($node)
	{
		if($thinkNodes = self::GetAllTagsByName($node, 'think'))
		{
			foreach ($thinkNodes as $think)
			{
				// process $think
				self::ProcessTemplate($think);
				
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
					// clear any math operator safe
					$value = self::ClearMathematicalOperators($value);
					$name = self::ClearMathematicalOperators($name);

					// save
					self::$_user->SetProp($name, $value);
					self::$_user->Save();
				}
				
				// remove node
				$node->removeChild($setNode);
			}
		}
		
		
	}


	static private function CompileDel($node)
	{
		if($sets = self::GetAllTagsByName($node, 'del'))
		{
			foreach ($sets as $setNode)
			{
				$name = false;
				$value = false;
				
				// check for 2.0 model
				if($name = self::GetAllTagsByName($setNode, 'name', true))
					$name = $name->nodeValue;
				//
				
				// check for name in old aiml model
				if($setNode->getAttribute('name') != '')
					$name = $setNode->getAttribute('name');
				//
				
				
				// del data for user
				if($name)
				{
					self::$_user->DelProp($name);
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
					if($getNode->getAttribute('name') != '')
						$value = self::$_domDoc->createTextNode($getNode->getAttribute('name'));
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
	
	static private function CompileBot($node)
	{
		if($bots = self::GetAllTagsByName($node, 'bot'))
		{
			foreach ($bots as $botNode)
			{
				$name = false;
				$value = '';
		
				// check for another tags
				if($name = self::GetAllTagsByName($botNode, 'name', true))
				{
					$value = self::$_domDoc->createTextNode(
									self::ProcessTemplate($name)
							   );
			    	
				}else{
					// check for name in old aiml model
					if($botNode->getAttribute('name') != '')
						$value = self::$_domDoc->createTextNode($botNode->getAttribute('name'));
					//
				}
				
				// load value from db
				$value = self::$_domDoc->createTextNode(
					self::$_bot->GetProp($value->nodeValue));
				
				// replace child for the value
				$node->replaceChild($value, $botNode);
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
				// fix for loop error in srai and that in same category
				array_push(self::$_data['that'], '');
				
				// re-find another response for srai and replace
				$newNode = self::$_domDoc->createTextNode(
					self::Find(
						self::ProcessTemplate($srai)));
				


				$node->replaceChild($newNode, $srai);
			}
		}
		
	}
	
	static private function CompileInput($node)
	{
		// search for input tag
		if($inputs = self::GetAllTagsByName($node, 'input'))
		{
			foreach ($inputs as $inputNode)
			{
				$value = ''; 
				$index = 0;
				
				// get index
				if($inputNode->getAttribute('index') != '')
				{
					$index = intval($inputNode->getAttribute('index'));
					$index--;
				} 
				
				// get value
				if(self::GetInput($index) !== false)
					$value = self::GetInput($index); 
				// 
				
				// replace child for the value
				$node->replaceChild(self::$_domDoc->createTextNode($value), $inputNode);
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
					
					if(
						$name == $conditionNode->getAttribute('value') 
						|| 
						(
							$varValue != '' 
							&& 
							$conditionNode->getAttribute('value') == 'true'
						)
						||
						(
							$varValue == ''
							&&
							$conditionNode->getAttribute('value') == 'false'
						)
					)
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
				if($lis = self::GetAllTagsByName($conditionNode, 'li'))
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
									||
									(
										$liNode->getAttribute('value') == 'false'
										&&
										$varValue == ''
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
			// check patterns
			if(self::CheckPattern($category))
			{
				// set topic
				$domNode->nodeName == 'topic' ? self::SetTopic($domNode) : self::SetDefaultTopic(); 
				
				return $category;
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
	static private function CheckPattern($category)
	{
		$patterns = self::GetAllTagsByName($category, 'pattern');
		$template = self::GetAllTagsByName($category, 'template', true);
		
		// search for any math pattern
		foreach ($patterns as $pattern)
		{
			if (self::ValidatePattern(self::$_input, $pattern->nodeValue, $template))
			{
				// looking for that tag
				if($that = self::GetAllTagsByName($category, 'that', true))
				{
					if(count(self::$_data['that']) > 0)
					{
						// check if same last response
						return
							self::ValidatePattern(
								self::GetLastThat(),
								$that->nodeValue,
								$template
								); 
					}
					// have a that tag but no last responses
					return false;
				}
				// no have that tag
				return true;
			}
		}
		
		// no have pattern tag
		return false;
	}

	static private function SetMathematicalOperators($input)
	{
		// mathematical operators safe
		$input = str_replace('+', 'zplusz', $input);
		$input = str_replace('-', 'zminusz', $input);
		$input = str_replace(' x ', 'zmultiplyz', $input);
		$input = str_replace('*', 'zmultiplyz', $input);
		$input = str_replace('/', 'zdividez', $input);
		$input = str_replace('.', 'zpointz', $input);

		return $input;
	}

	static private function ReturnMathematicalOperators($input)
	{
		// mathematical operators safe
		$input = str_replace('zplusz', '+', $input);
		$input = str_replace('zminusz', '-', $input);
		$input = str_replace('zmultiplyz', '*', $input);
		$input = str_replace('zdividez', '/', $input);
		$input = str_replace('zpointz', '.', $input);

		return $input;
	}


	static private function ClearMathematicalOperators($input)
	{
		// mathematical operators safe
		$input = str_replace('zplusz', '', $input);
		$input = str_replace('zminusz', '', $input);
		$input = str_replace('zmultiplyz', '', $input);
		$input = str_replace('zdividez', '', $input);
		$input = str_replace('zpointz', '', $input);

		return $input;
	}
	
	static private function ValidatePattern($input, $pattern, $template)
	{
		$old_input = $input;
		$old_pattern = $pattern;
		
		$input = trim($input);
		$input = strtolower($input);
		
		$input = self::PreParseInput($input);
		$pattern = self::RemoveAccentuarion($pattern);
		
		// clear
		$pattern = trim($pattern);
		$pattern = strtolower($pattern);
		$pattern = str_replace('_', '*', $pattern); // _ = * in aiml

		// remove some chars
		$pattern = str_replace('+', '', $pattern);
		$pattern = str_replace('-', '', $pattern);
		$pattern = str_replace('[', '', $pattern);
		$pattern = str_replace(']', '', $pattern);
		$pattern = str_replace('(', '', $pattern);
		$pattern = str_replace(')', '', $pattern);
		$pattern = str_replace('?', '', $pattern);
		$pattern = str_replace('!', '', $pattern);
		$pattern = str_replace(',', '', $pattern);
		$pattern = str_replace('.', '', $pattern);
		
		// replace pattern
		$pattern = str_replace(' * ', ' (.+) ', $pattern);
		$pattern = str_replace('* ', '(.+) ', $pattern);
		$pattern = str_replace(' *', ' (.+)', $pattern);
		$pattern = str_replace('*', '(.+)', $pattern);
		$pattern = str_replace(' # ', ' (.*) ', $pattern);
		$pattern = str_replace(' #', ' (.*)', $pattern);
		$pattern = str_replace('# ', '(.*) ', $pattern);
		$pattern = str_replace('#', '(.*)', $pattern);
		
		
		
		// form regular expression
		$regex = '/';
		$regex .= '^' . $pattern . '$';
		$regex .= '/i';
		
		
		// check expr
		$is_match = preg_match($regex, $input, $matches) ? true : false;
		// echo '(', $input, '-', $regex, '){' . ($is_match ? 't' : 'f') . '}[' . self::GetLastThat() . ']';

		// set star(s)
		if(count($matches) > 1)
		{
			// replace all srai tag inside templateNode
			// echo '(['.$old_input.','.$old_pattern.']setstar:' . print_r($matches, true) . ')';
			array_shift($matches);
			self::$_star = $matches;
			
			self::CompileStar($template);
			// fazer com o que star tag seja subistiuida aqui
		}
		
		// return math
		return $is_match;
	} 
	
	
	static private function SetDefaultTopic()
	{
		self::$_data['topics'] = array();
	}
	
	static private function SetInput($input)
	{
		self::$_input = $input;
		
		// add response
		array_push(self::$_data['input'], $input);
		
		// if array length is more than 10, cut-off
		if(count(self::$_data['input']) > 10)
			array_shift(self::$_data['input']);
		//
	}
	
	
	/**
	 * Save last 10 response
	 * @param string $lastResponse
	 */
	static private function SetResponse($lastResponse)
	{
		// add response
		array_push(self::$_data['that'], $lastResponse);
		
		// if array length is more than 10, cut-off
		if(count(self::$_data['that']) > 10)
			array_shift(self::$_data['that']);
		//
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
	
	static private function GetStar($index = 0)
	{
		// check consoudhiayw
		if($index >= count(self::$_star))
			return false;
		//

		return count(self::$_star) == 0 ? false : self::$_star[$index];
	} 
	
	static private function GetInput($index = 0)
	{
		// check consoudhiayw
		if($index > count(self::$_data['input']))
			return false;
		//
		
		$reverseArray = array_reverse(self::$_data['input']);
		return count(self::$_data['input']) == 0 ? false : $reverseArray[$index];
	} 
	
	static private function GetLastThat()
	{
		if(count(self::$_data['that']) == 0)
			return '';
		//
		
		$that = self::$_data['that'][count(self::$_data['that'])-1];
		$that = trim($that);
		$that = preg_replace("/[[:punct:]]/", "", $that);
		
		return $that; 
	}
	
	static public function PreParseInput($input)
	{
		// remove accents
		$input = self::RemoveAccentuarion($input);

		$input = self::SetMathematicalOperators($input);
		
		// remove line breaks
		$input = preg_replace('/^\s+|\n|\r|\s+$/m', '', $input);
		
		// remove ponctuation
		$input = str_replace(array('?', '!', '.'), '', $input);
		$input = preg_replace("/[[:punct:]]/", "", $input);
		
		return $input;
	} 
	
	static public function RemoveAccentuarion($input)
	{
		$a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
		$b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');
		$input = str_replace($a, $b, $input);
		
		$input = str_replace('^', '', $input);
		$input = str_replace('~', '', $input);
		$input = str_replace('\'', '', $input);
		$input = str_replace('´', '', $input);
		$input = str_replace('<', '', $input);
		$input = str_replace('>', '', $input);
		
		return $input; 
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

		// $x = gettype($domNode) == 'string' ? $domNode : 'obj';

		// echo '<p>$tagName(', $tagName, ')[' . $x . ']';//, ' | ', $domNode;
		
		foreach(self::$_domXPath->query($tagName, $domNode) as $node)
			$arrResponse[] = $node;
		//
		
		return count($arrResponse) > 0 ? $getOne ? $arrResponse[0] : $arrResponse : false;
	}
}