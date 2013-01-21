<?php
/**
 * @package Abricos
 * @subpackage Jofolio
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

if (empty(Abricos::$user->info['userid'])){ return;  }

$modFM = Abricos::GetModule('filemanager');
if (empty($modFM)){ return; }

$brick = Brick::$builder->brick;
$var = &$brick->param->var;

if (Abricos::$adress->dir[2] !== "go"){ return; }

$uploadFile = FileManagerModule::$instance->GetManager()->CreateUploadByVar('image');
$uploadFile->maxImageWidth = 400;
$uploadFile->maxImageHeight = 400;
$uploadFile->ignoreFileSize = true;
$uploadFile->isOnlyImage = true;
$uploadFile->imageConvertTo = "png";
$uploadFile->folderPath = "system/".date("d.m.Y", TIMENOW);

$error = $uploadFile->Upload();
if ($error == 0){
	$var['command'] = Brick::ReplaceVarByData($var['ok'], array(
		"fhash" => $uploadFile->uploadFileHash,
		"fname" => $uploadFile->fileName
	));
}else{
	$var['command'] = Brick::ReplaceVarByData($var['error'], array(
		"errnum" => $error
	));

	$brick->content = Brick::ReplaceVarByData($brick->content, array(
		"fname" => $uploadFile->fileName
	));
}
	
?>