<?php
/**
 * @package Abricos
 * @subpackage Team
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'dbquery.php';
require_once 'classes.php';

class TeamModuleManager extends Ab_ModuleManager {
	
	/**
	 * @var TeamModule
	 */
	public $module = null;
	
	/**
	 * @var TeamModuleManager
	 */
	public static $instance = null; 
	
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
	
	private $_teamCache = array();
	public function CacheClear(){
		$this->_teamCache = array();
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
	 * Участник
	 * 
	 * @param integer $userid
	 * @param string $modname список групп модуля
	 * @param integer $teamid дополнительно указать отношение к этой группе
	 * @param string $invite
	 */
	/*
	public function Member($userid, $modname = '', $teamid = 0, $invite = ''){
		if (!$this->IsViewRole()){ return null; }
		
		$ret = new stdClass();
		$ret->userid = $userid;
		
		$upman = Abricos::GetModule('uprofile')->GetManager();
		
		$ret->user = $upman->Profile($userid, true);
		if (empty($ret->user)){
			sleep(5);
			return null;
		}
		
		$uteams = $this->TeamListByMember($userid, $modname, $invite);
		$ret->teams = $uteams;
		
		if ($teamid > 0){ // запрос проверки принадлежность к группе
			$find = false;
			foreach($uteams as $ut){
				if ($ut['id'] == $teamid){
					$find = true;
					$ret->checkteaminfo = &$ut;
					$ret->reluser = $upman->Profile($ut['ruid'], true);
					break;
				}
			}
			if ($find){
				$ret->checkteam = $teamid;
			}
		}
		
		if (!empty($invite) && !is_null(InviteModule::$instance->currentInvite)){
			$ret->invite = InviteModule::$instance->currentInvite->GetData();
		}

		return $ret;
	}
	/**/
	

	

	/**
	 *  
	 *  
	 * 
	 * @param integer $userid
	 */
	
	/**
	 * Пользователь принял приглашение
	 *
	 * @param integer $userid
	 * @param string $modname
	 * @param integer $teamid
	 * @param string $invite
	 * @param boolean $flag true - принять, false - отказать
	 */
	public function UserInviteAccept($userid, $modname, $teamid, $invite, $flag){
		$currentUserId = $this->userid;
	
		$member = $this->Member($userid, $modname, $teamid, $invite);
	
		if (is_null($member)){ return false; }
	
		$ti = $member->checkteaminfo;
	
		if (empty($ti)){ return false; }
	
		// приглашение по инвайту
		if (!empty($member->invite)){
			$currentUserId = $member->invite->user['id'];
		}
	
		// принять приглашение может только автор
		if ($userid != $currentUserId){
			return false;
		}
	
		// пользователь должен быть гостем и ожидать подтвердения
		if ($ti['ismbr'] != 0 || $ti['isinv'] != 1){
			return false;
		}
	
		if ($flag){ // принял
			TeamQuery::MemberInviteSetAccept($this->db, $teamid, $userid);
		}else{ // отказал
			TeamQuery::MemberInviteSetReject($this->db, $teamid, $userid);
		}
	
		return true;
	}
	
	
	/**
	 * Этот пользователь запросил вступление в группу teamid
	 * 
	 * @param integer $teamid
	 */
	public function MemeberJoinRequestSet($teamid){
		if (!$this->IsWriteRole()){ return null; }
		
		$rd = $this->Team($teamid);
		if (is_null($rd) || empty($rd->team)){ return null; }
		
		if ($rd->team['anj'] == 1){ 
			// вступить в группу может каждый
			TeamQuery::UserSetMember($this->db, $teamid, $this->userid);
		}else{ 
			// необходимо подтверждение на вступление
			TeamQuery::MemeberJoinRequestSet($this->db, $teamid, $this->userid);
		}
	}
	
	/**
	 * Список групп в которых участвует (запрос на вступление, приглашение) пользователь
	 * 
	 * Если указан инвайт, то результат выдачи с учетом его пользователя, но только для гостей системы
	 * 
	 * Инвайт нужен для того, чтобы идентифицировать будущего пользователя и выдать ему список, как будто он авторизован
	 * 
	 * @param integer $userid
	 * @param string $modname
	 * @param string $invite
	 */
	public function TeamListByMember($userid, $modname = '', $invite = ''){
		if (!$this->IsViewRole()){ return null; }
		
		$currentUserId = $this->userid;
		
		if ($this->userid == 0 && !empty($invite)){ // запрашивает гость, проверим, нет ли инвайта на запрос
			Abricos::GetModule('invite');
			$manInv = InviteModule::$instance->GetManager();
			
			$user = $manInv->UserByInvite($invite);
			if (!empty($user)){
				$currentUserId = $user['id'];
			}
		}
		
		$tmids = array();
		if ($userid != $currentUserId){ 
			// еще нужно добавить список тех групп, где этот юзер в стадии ожидания вступления
			// и в которых текущий пользователь является админом
			
			$tmids = $this->TeamListByMemberIsAdmin();
		}
		
		$rows = TeamQuery::TeamListByMember($this->db, $userid, $modname, $currentUserId, $tmids);
		
		return $this->ToArray($rows);
	}

	/**
	 * Список сообществ в которых userid является админом
	 * 
	 * @param integer $userid
	 */
	public function TeamListByMemberIsAdmin($userid = 0){
		if (!$this->IsViewRole()){ return null; }
		
		if ($userid == 0){
			$userid = $this->userid;
		}
		
		$rows = TeamQuery::TeamListByMemberIsAdmin($this->db, $userid);
		return $this->ToArray($rows);
	}
	
	/* * * * * * Функции по управлению участника группы * * * * * */
	
}

?>