<?php
/**
 * @package Abricos
 * @subpackage Team
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'classes.php';
require_once 'dbquery.php';

class TeamModuleManager extends Ab_ModuleManager {
	
	/**
	 * @var TeamModuleManager
	 */
	public static $instance = null;
	 
	/**
	 * @var TeamModule
	 */
	public $module = null;
	
	/**
	 * Настройка модуля
	 * @var TeamConfig
	 */
	public $config;
	
	public function __construct(TeamModule $module){
		TeamModuleManager::$instance = $this;
		
		parent::__construct($module);
		
		$this->config = new TeamConfig(Abricos::$config['module']['team']);
	}
	
	public function IsAdminRole(){
		return $this->IsRoleEnable(TeamAction::ADMIN);
	}
	
	public function IsWriteRole(){
		if ($this->IsAdminRole()){ return true; }
		return $this->IsRoleEnable(TeamAction::WRITE);
	}
	
	public function IsViewRole(){
		if ($this->IsWriteRole()){ return true; }
		return $this->IsRoleEnable(TeamAction::VIEW);
	}
	
	public function AJAX($d){
		switch($d->do){
			case 'teammodulename': return $this->TeamModuleName($d->teamid);
		}
		return null;
	}
	
	public function ToArray($rows, &$ids1 = "", $fnids1 = 'id', &$ids2 = "", $fnids2 = '', &$ids3 = "", $fnids3 = ''){
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			array_push($ret, $row);
			if (is_array($ids1)){ $ids1[$row[$fnids1]] = $row[$fnids1]; }
			if (is_array($ids2)){ $ids2[$row[$fnids2]] = $row[$fnids2]; }
			if (is_array($ids3)){ $ids3[$row[$fnids3]] = $row[$fnids3]; }
		}
		return $ret;
	}
	
	public function ToArrayId($rows, $field = "id"){
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			$ret[$row[$field]] = $row;
		}
		return $ret;
	}
	
	/**
	 * Получить имя модуля сообщества наследуемого приложения
	 * @param integer $teamid
	 */
	public function TeamModuleName($teamid){
		if (!$this->IsViewRole()){ return null; }
		
		return TeamQuery::TeamModuleName($this->db, $teamid);
	}
	
	/**
	 * @param string $moduleName
	 * @return TeamManager
	 */
	public function GetTeamManager($moduleName){
		$mod = Abricos::GetModule($moduleName);
		if (empty($mod)){
			return null;
		}
		
		$modMan = $mod->GetManager();
		
		return @$modMan->GetTeamManager();
	}
	
	public function GetTeamAppManager($modName, $appName){
		$mod = Abricos::GetModule($modName);
		if (empty($mod)){ return null; }
		
		if (!method_exists($mod, 'Team_GetAppManager')){
			return null;
		}
		return $mod->Team_GetAppManager($appName);
	}
	
	private $_cacheTeam = array();
	
	/**
	 * Сообщество
	 * 
	 * @param integer $teamid
	 * @return Team
	 */
	public function Team($teamid, $moduleName = ''){
		if (isset($this->_cacheTeam[$teamid])){
			return $this->_cacheTeam[$teamid];
		}
		if (empty($moduleName)){
			$moduleName = TeamQuery::TeamModuleName($this->db, $teamid);
			if (empty($moduleName)){ return null; }
		}

		$mod = Abricos::GetModule($moduleName);
		if (empty($mod)){ return null; }
		
		$modMan = $mod->GetManager();
		
		$tMan = @$modMan->GetTeamManager();
		if (empty($tMan)){
			$this->_cacheTeam[$teamid] = null;
			return null;
		}
		
		return $this->_cacheTeam[$teamid] = $tMan->Team($teamid);
	}
	
	
	public function TeamModuleNameList(){
		$ret = array();
		if (!$this->IsViewRole()){ return null; }
		
		$rows = TeamQuery::TeamModuleNameList($this->db);
		while (($row = $this->db->fetch_array($rows))){
			array_push($ret, $row['m']);
		}
		
		return $ret;
	}
	
	public function FileAddToBuffer($fhash, $fname){
		if (!$this->IsWriteRole()){ return null; }
		
		TeamQuery::FileAddToBuffer($this->db, $this->userid, $fhash, $fname);
		$this->FileBufferClear();
	}
	
	public function FileBufferClear(){
		$mod = Abricos::GetModule('filemanager');
		if (empty($mod)){ return; }
		$mod->GetManager();
		$fm = FileManager::$instance;
		$fm->RolesDisable();
	
		$rows = TeamQuery::FileFreeFromBufferList($this->db);
		while (($row = $this->db->fetch_array($rows))){
			$fm->FileRemove($row['fh']);
		}
		$fm->RolesEnable();
		TeamQuery::FileFreeListClear($this->db);
	}
}

?>