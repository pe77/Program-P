<?php

namespace Pe77\ProgramP\Classes;

/**
 * 
 * @author P.
 *
 */
class Response
{
	private $_topics = array();
	private $_response = '';
	
	
	/**
	 * Add Topic of conversation
	 * @param string $topic
	 */
	public function AddTopic($topic)
	{
		$this->_topics[] = $topic;
	}
	
	/**
	 * Get all topics if have topic inside another topic
	 * @return array - All topics
	 */
	public function GetTopics()
	{
		return array_reverse($this->_topics);
	}
	
	/**
	 * Return (string) topic of last topic or false if default topic
	 * @return String|False  
	 */
	public function GetMainTopic()
	{
		$topicRevert = array_reverse($this->_topics);
		return count($this->_topics) > 0 ? $topicRevert[count($this->_topics-1)] : false; 
	}
	
	/**
	 * Set final response
	 * @param string $response
	 */
	public function SetResponse($response)
	{
		$this->_response = (string)$response;
	}
	
	/**
	 * ToString...
	 * @return string - Response
	 */
	public function __toString()
	{
		return print_r($this->_response, true);
	}
}