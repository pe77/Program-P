<?php


require 'config.php';
require 'vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Pe77\ProgramP\ProgramP;
use Pe77\ProgramP\ProgramP\Classes\Response;

// load config
// $config = Yaml::parse('config.yml');

print_r($config);
die('-');


// check request type
if(!isset($_REQUEST['requestType']))
	die();
//

// ini
$programP = new ProgramP($config);
$bot	  = $programP->GetBot('cenouro');
$user	  = $programP->GetUser($_SERVER['REMOTE_ADDR']);


// talk
if($_REQUEST['requestType'] == 'talk')
{
	$bot->SetProp('nome', 'Cenouro');
	$bot->Save();
	
	$response = $programP->GetResponse($user, $bot, $_REQUEST['input']);
	
	echo $response;
}


// clear
if($_REQUEST['requestType'] == 'forget')
{
	$user->ClearAllProp();
	echo '1';
}