<?php

namespace Pe77\ProgramP;

use Pe77\ProgramP\Classes\User;

use Pe77\ProgramP\Classes\Bot;
use Pe77\ProgramP\Classes\Database\Connect;

class ProgramP
{
    private $_bots = array();
    private $_users = array();
    private $_config;

    function __construct($config)
    {
        $this->_config = $config;

        Connect::init($this->_config['db']);
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

    public function GetConfig()
    {
        return $this->_config;
    }

    /**
     * Return a Bot
     * @param string $unique - Unique Identification (AIML file in dir, without '.aiml') 
     * @return Bot:
     */
    public function GetBot($unique)
    {
        if (!array_key_exists($unique, $this->_bots)) 
            $this->_bots[$unique] = $this->CreateBot($unique);
        

        return $this->_bots[$unique];
    }
    
	/**
     * Load user by unique key (name+IP exemple)
     * @param string $userUnique
     */
    function GetUser($unique)
    {
        if (!array_key_exists($unique, $this->_users)) 
            $this->_users[$unique] = new User($this, $unique);
        

        return $this->_users[$unique];
    }

    /**
     * Return total bots in Program P
     * @return number - Total bots
     */
    public function CountBots()
    {
        return count($this->_bots);
    }

    /**
     * Create Bot
     * @param string $botName - unique key for identific bot, and bot aiml file to.
     * @return Bot
     */
    public function CreateBot($unique)
    {
        $fileFullName = $this->_config['aiml']['dir'] . '/' . $unique.'.aiml';


        // check if the file exists
        if(!file_exists($fileFullName))
        {
            throw new \Exception("File AIML not found in : " . $fileFullName);
        }

        // read aiml file
        $aimlString = file_get_contents($fileFullName);

        // @todo check aiml format

        // return bot
        return new Bot($this, $unique, $aimlString);
    }
}
