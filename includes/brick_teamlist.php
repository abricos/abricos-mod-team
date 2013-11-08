<?php
/**
 * @package Abricos
 * @subpackage Team
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
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

?>