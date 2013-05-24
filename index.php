<?php
 
require 'src/program-p/ProgramP.php';
require 'src/program-p/utils/spyc.php';

$config = Spyc::YAMLLoad('src/program-p/config.yml');

$programP = new ProgramP($config);
$bot	  = $programP->GetBot('cenouro');