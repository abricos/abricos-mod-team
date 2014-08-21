<?php 
/**
 * @package Abricos
 * @subpackage Team
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class TeamAppBrickInfo {
	public $name;
	public $modName;
	public $param;
	
	public function __construct($modName){
		$this->modName = $modName;
		$this->param = array();
	}
	
	public function SetName($name){
		$this->name = $name;
		return $this;
	}
	
	public function SetParam($param){
		foreach($param as $key => $value){
			$this->param[$key] = $value;
		}
		return $this;
	}
	
	public function Set($name, $param){
		$this->name = $name;
		$this->SetParam($param);
		return $this;
	}
}

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
	
	/**
	 * @return TeamAppBrickInfo
	 */
	public function GetBrickInfo(){ return null; }
	
	/**
	 * @return Ab_CoreBrick
	 */
	public function GetContentBrick(){
		
		$bkInfo = $this->GetBrickInfo();
		
		if (empty($bkInfo)){ return null; }
		
		if (empty($bkInfo->modName)){
			$bkInfo->modName = $this->manager->moduleName;
		}
		
		$bkInfo->SetParam(array(
			"builder" => $this
		));
		
		return Brick::$builder->LoadBrickS($bkInfo->modName, $bkInfo->name, null, array(
			"p" => $bkInfo->param
		));
	}
}

?>