<?php

require 'vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Pe77\ProgramP\ProgramP;

// load config
$config = Yaml::parse('config.yml');

// ini
$programP = new ProgramP($config);
$bot	  = $programP->GetBot('cenouro');
