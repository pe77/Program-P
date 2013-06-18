<?php


require 'vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Pe77\ProgramP\ProgramP;
use Pe77\ProgramP\ProgramP\Classes\Response;

// load config
$config = Yaml::parse('config.yml');

// ini
$programP = new ProgramP($config);
$bot	  = $programP->GetBot('cenouro');
$user	  = $programP->GetUser($_SERVER['REMOTE_ADDR']);

$bot->SetProp('name', 'Cenouro');
$bot->SetProp('gender', 'homem');
$bot->Save();

$user->SetProp('nome', $_REQUEST['user']);
$user->Save();

$response = $programP->GetResponse($user, $bot, $_REQUEST['input']);

echo $response;