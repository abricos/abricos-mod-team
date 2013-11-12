<?php
/**
 * @package Abricos
 * @subpackage Team
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;

$app = TeamModule::$instance->currentTeamApp;
$team = TeamModule::$instance->currentTeam;

$appBrick = $app->GetBrickBuilder()->GetContentBrick($team->id);

if (!empty($appBrick)){
	$brick->content = $appBrick->content;
}

?>