<?php

require 'vendor/autoload.php';

// use Pe77\ProgramP;
use Symfony\Component\Yaml\Yaml;

var_dump(Yaml::parse('src/program-p/config.template.yml'));




// require 'src/program-p/ProgramP.php';
// require 'src/program-p/utils/spyc.php';

// $config = Spyc::YAMLLoad('src/program-p/config.yml');

// $programP = new ProgramP($config);

// $bot	  = $programP->GetBot('cenouro');
// print_r($bot); 
