<?php

require 'vendor/autoload.php';

$programP = new ProgramP($config);
$bot	  = $programP->GetBot('cenouro');

// use Pe77\ProgramP;
use Symfony\Component\Yaml\Yaml;

require 'src/program-p/ProgramP.php';
// require 'src/program-p/utils/spyc.php';

$config = Yaml::parse('src/program-p/config.template.yml');

$programP = new ProgramP($config);

$bot	  = $programP->GetBot('cenouro');
