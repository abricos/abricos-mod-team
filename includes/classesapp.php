<?php 
/**
 * @package Abricos
 * @subpackage Team
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'classesappman.php';

class TeamAppNavigator {

	/**
	 * @var TeamAppManager
	 */
	public $manager;
	public $isURL;

	public function __construct($manager, $isURL = false){
		$this->manager = $manager;
		$this->isURL = $isURL;
	}

	public function URL(Team $team){
		$url = $this->TeamView($team);
		return $url."m_".$this->manager->moduleName."/a_".$this->manager->name;
	}
	
	public function TeamView(Team $team){
		return $team->Manager()->Navigator($this->isURL)->TeamView($team->id);
	}
}

class TeamAppInitData {
	
	/**
	 * @var TeamAppManager
	 */
	public $manager;
	
	public function __construct($manager){
		$this->manager = $manager;
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		
		return $ret;
	}
}


?>