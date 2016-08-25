<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
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
