<?php

namespace Pe77\ProgramP\Classes;

class Bot extends Human
{
    var $_aimlString;

    public function __construct($programp, $unique, $aimlString)
    {
        // save aiml, unique key
        $this->_programp    = $programp;
        $this->_aimlString  = $aimlString;
        $this->_unique      = $unique;
        $this->_type        = "bot";

        // load/create prop
        $this->LoadProp();
    }

    function LoadProp()
    {
        // @todo - load props in aiml string and save

        parent::LoadProp();
    }
}
