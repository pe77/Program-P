<?php

require 'vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Pe77\ProgramP\ProgramP;


// load config
$config = Yaml::parse('config.yml');

// ini
$programP = new ProgramP($config);
$bot	  = $programP->GetBot('cenouro');
$user	  = $programP->GetUser('P.');

$bot->SetProp('name', 'Cenouro');
$bot->SetProp('gender', 'male');
$bot->DelProp('nome');
$bot->Save();

$user->SetProp('name', 'P.');
$user->SetProp('gender', 'male');
$user->Save();

$response = $programP->GetResponse($user, $bot, "bone");

echo $response;