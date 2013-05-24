<?php

namespace Pe77\ProgramP;

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

        // Connect::init($this->_config['db']);
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


    public function GetConfig()
    {
        return $this->_config;
    }

    public function GetBot($unique)
    {
        if (!array_key_exists($unique, $this->_bots)) {
            $this->_bots[$unique] = $this->CreateBot($unique);
        }

        return $this->_bots[$unique];
    }

    public function CountBots()
    {
        return count($this->_bots);
    }

    /**
     * Load Bot
     * @param string $botName - unique key for identific bot, and bot aiml file to.
     * @return Bot/Boolean - if bot not exist return false
     */
    private function CreateBot($unique)
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
