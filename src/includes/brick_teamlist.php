<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$uri = $brick->param->param['uri'];

$man = TeamModule::$instance->currentTeamManager;

$uri = Brick::ReplaceVarByData($uri, array(
    'teammod' => $man->moduleName
));

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "uri" => $uri
));
