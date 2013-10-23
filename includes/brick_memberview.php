<?php
/**
 * @package Abricos
 * @subpackage Sportsman
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$uri = $brick->param->param['uri'];

$mod = TeamModule::$instance;
$team = $mod->currentTeam;

$uri = Brick::ReplaceVarByData($uri, array(
	'teammod' => $team->module,
	'membermod' => $mod->currentMemberMod->name,
	'teamid' => $team->id,
	'memberid' => $mod->currentMemberId
));

$brick->content = Brick::ReplaceVarByData($brick->content, array(
	"uri" => $uri
));

?>