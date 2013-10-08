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
	
	public function __construct(TeamModule $module){
		parent::__construct($module);
		
		TeamModuleManager::$instance = $this;
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
			case 'userfindbyemail': return $this->UserFindByEmail($d->email);
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
	 * Поиск пользователя по email
	 */
	public function UserFindByEmail($email){
		$ret = new stdClass();
		$ret->email = $email;
		$ret->user = null;
		
		if (!$this->IsWriteRole()){
			sleep(5);
			return $ret;
		}
		if (!$this->IsAdminRole()){
			sleep(1);
		}
		
		if (!Abricos::$user->GetManager()->EmailValidate($email)){
			return $ret;
		}
		
		$user = TeamQuery::UserByEmail($this->db, $email);
		if (!empty($user)){
			$ret->user = $user;
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
	
	private $_cacheTeam = array();
	
	/**
	 * Сообщество
	 * 
	 * @param integer $teamid
	 * @return Team
	 */
	public function Team($teamid){
		if (isset($this->_cacheTeam[$teamid])){
			return $this->_cacheTeam[$teamid];
		}
		$modName = TeamQuery::TeamModuleName($this->db, $teamid);
		if (empty($modName)){ return null; }

		$mod = Abricos::GetModule($modName);
		if (empty($mod)){ return null; }
		
		$modMan = $mod->GetManager();
		
		$tMan = @$modMan->GetTeamManager();
		if (empty($tMan)){
			$this->_cacheTeam[$teamid] = null;
			return null;
		}
		
		return $this->_cacheTeam[$teamid] = $tMan->Team($teamid);
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