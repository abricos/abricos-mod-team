<?php
/**
 * @package Abricos
 * @subpackage Team
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

if (empty(Abricos::$user->id)){ return;  }

$modFM = Abricos::GetModule('filemanager');
if (empty($modFM)){ return; }

$brick = Brick::$builder->brick;
$var = &$brick->param->var;

if (Abricos::$adress->dir[2] !== "go"){ return; }

$fmMan = FileManagerModule::$instance->GetManager();
$uploadFile = $fmMan->CreateUploadByVar('image');
$uploadFile->ignoreUploadRole = true;
$uploadFile->maxImageWidth = 400;
$uploadFile->maxImageHeight = 400;
$uploadFile->ignoreFileSize = true;
$uploadFile->isOnlyImage = true;
$uploadFile->imageConvertTo = "png";
// $uploadFile->folderPath = "system/".date("d.m.Y", TIMENOW);

$error = $uploadFile->Upload();
if ($error == 0){
	
	FileManagerModule::$instance->EnableThumbSize(array(
		array("w"=>50, "h"=>50),
		array("w"=>100, "h"=>100),
		array("w"=>200, "h"=>200)
	));
	
	$fHash = $uploadFile->uploadFileHash;
	$fName = $uploadFile->fileName;
	$var['command'] = Brick::ReplaceVarByData($var['ok'], array(
		"fhash" => $fHash,
		"fname" => $fName
	));
	TeamModule::$instance->GetManager()->FileAddToBuffer($fHash, $fName);
}else{
	$var['command'] = Brick::ReplaceVarByData($var['error'], array(
		"errnum" => $error
	));

	$brick->content = Brick::ReplaceVarByData($brick->content, array(
		"fname" => $uploadFile->fileName
	));
}
	
?>