<?php 
/**
 * @package Abricos
 * @subpackage Team
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class TeamAppBrickBuilder {
	
	/**
	 * @var TeamAppManager
	 */
	public $manager;
	
	/**
	 * @var Team
	 */
	public $team;
	
	public function __construct(TeamAppManager $manager, Team $team){
		$this->manager = $manager;
		$this->team = $team;
	}
	
	public function GetBrickName(){
		return '';
	}
	
	
	/**
	 * @return Ab_CoreBrick
	 */
	public function GetContentBrick(){
		return null;
	}
}

?>