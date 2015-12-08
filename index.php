<?php


require 'config.php';
require 'vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Pe77\ProgramP\ProgramP;
use Pe77\ProgramP\ProgramP\Classes\Response;


// check request type
if(!isset($_REQUEST['requestType']))
	die();
//

// ini
$programP = new ProgramP($config);
$bot	  = $programP->GetBot('cenouroresponde');
$user	  = $programP->GetUser($_SERVER['REMOTE_ADDR']);


// talk
if($_REQUEST['requestType'] == 'talk')
{
	$bot->SetProp('nome', 'Cenouro');
	$bot->Save();

	$response = $programP->GetResponse($user, $bot, $_REQUEST['input']);
	
	header("Content-Type: text/plain");
	echo trim($response);
}


// clear
if($_REQUEST['requestType'] == 'forget')
{
	$user->ClearAllProp();
	echo '1';
}