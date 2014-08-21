<?php
/**
 * @package Abricos
 * @subpackage Team
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$p = &$brick->param->param;
$v = &$brick->param->var;

$man = TeamModule::$instance->GetManager();

$modNames = $man->TeamModuleNameList();

$lst = "";
foreach ($modNames as $modName){
	$lst .= Brick::ReplaceVarByData($v['row'], array(
		"lnk" => "/team/m_".$modName."/",
		"tl" => $modName
	));
}

$brick->content = $lst;

?>