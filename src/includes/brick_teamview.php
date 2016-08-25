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

$mod = TeamModule::$instance;
$team = $mod->currentTeam;

$uri = Brick::ReplaceVarByData($uri, array(
    'teammod' => !empty($team->parentModule) ? $team->parentModule : $team->module,
    'teamid' => $team->id
));

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "uri" => $uri
));
