<?php 
/**
 * @package Abricos
 * @subpackage TeamMember
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

	public function URL(){
		if ($this->isURL){
			return Abricos::$adress->host."/".$this->manager->moduleName."/";
		}
		return "/".$this->manager->moduleName."/";
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