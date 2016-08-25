<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;

$app = TeamModule::$instance->currentTeamApp;
$team = TeamModule::$instance->currentTeam;

$appBrick = $app->GetBrickBuilder($team->id)->GetContentBrick();

if (!empty($appBrick)){
    $brick->content = $appBrick->content;
}
