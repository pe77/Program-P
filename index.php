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

// user id | usa o IP por padrão como identificador unico
$userId = $_SERVER['REMOTE_ADDR'];

// se foi enviado o ID substitui
if(isset($_REQUEST['uid']))
	$userId = $_REQUEST['uid'];
//

// ini
$programP = new ProgramP($config);
$bot	  = $programP->GetBot('cenouroresponde');
$user	  = $programP->GetUser($userId);


// talk
if($_REQUEST['requestType'] == 'talk')
{
	$bot->SetProp('nome', 'Cenouro');
	$bot->Save();

	// fixa o rand com base na perunta + uid
	$seed  = substr(base_convert(md5($userId . $_REQUEST['input']), 16, 10) , -10);
	srand($seed);


	$response = array(
		'status'=>1,
		'message'=>'',
		'data'=>null,
	);

	$response['message'] = $programP->GetResponse($user, $bot, $_REQUEST['input']);

	// revome line breaks, extra spaces and tabs
	$response['message'] = trim(preg_replace("/\s+/", " ", $response['message']));

	if($programP->GetData())
		$response['data'] = $programP->GetData();
	//
	
	header("Content-Type: application/json; charset=utf-8");
	echo json_encode($response);
}


// clear
if($_REQUEST['requestType'] == 'forget')
{
	$user->ClearAllProp();
	echo '1';
}