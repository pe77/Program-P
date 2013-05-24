<?php

namespace Pe77\ProgramP\Classes;

class Bot extends Human
{
    public $_aimlString;

    public function __construct($programp, $unique, $aimlString)
    {
        // save aiml
        $this->_aimlString  = $aimlString;
		
        parent::__construct($programp, $unique, "bot");
    }

    function LoadProp()
    {
        // @todo - load props in aiml string and add to save

        parent::LoadProp();
    }
}
