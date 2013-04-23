<?php 
/**
 * @package Abricos
 * @subpackage Team
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */
require_once 'dbquery.php';

class TeamManager {
	
	/**
	 * @var TeamManager
	 */
	public $modManager = null;

	/**
	 * Имя управляющего модуля
	 * @var string
	 */
	public $modname = '';

	/**
	 * @var Ab_Database
	 */
	public $db;
	
	/**
	 * @var User
	 */
	public $user;
	
	/**
	 * @var integer
	 */
	public $userid;
	
	/**
	 * @var Team
	 */
	public $TeamClass			= Team;
	public $TeamDetailClass		= TeamDetail;
	
	public $TeamUserRoleClass	= TeamUserRole;
	
	public $TeamListClass		= TeamList;
	
	public $MemberClass			= Member;
	public $MemberDetailClass	= MemberDetail;
	public $MemberListClass		= MemberList;
	
	public $TeamUserConfigClass	= TeamUserConfig;
		
	/**
	 * Информация о расширенной таблицы ролей пользовтеля
	 * Например для спортклуба:
	 * $this->fldExtTeamUserRole['sportclub_userrole'] = "isemployee,istrener,issportsman"; 
	 * 
	 * sportclub_userrole - таблица расширения в базе
	 * isemployee,istrener,issportsman - перечень полей в ней
	 */
	public $fldExtTeamUserRole = array();

	public $fldExtTeamDetail = array();
	
	/**
	 * @param TeamModuleManager $modManager
	 */
	public function __construct(Ab_ModuleManager $mman){
		$this->modManager = $mman;
		$this->modname = $mman->module->name;
		$this->db = $mman->db;
		$this->user = $mman->user;
		$this->userid = $mman->userid;
	}
	
	/**
	 * @param array $d
	 * @return Team
	 */
	public function NewTeam($d){ 
		return new $this->TeamClass($d);
	}
	
	/**
	 * @param Team $team
	 * @return TeamDetail
	 */
	public function NewTeamDetail(Team $team, $d){
		return new $this->TeamDetailClass($team, $d);
	}

	/**
	 * @param Team $team
	 * @param integer $userid
	 * @param array $d
	 */
	public function NewTeamUserRole(Team $team, $userid, $d){
		return new $this->TeamUserRoleClass($team, $userid, $d);
	}
	
	/**
	 * @return TeamList
	 */
	public function NewTeamList(){ return new $this->TeamListClass(); }
	
	/**
	 * @param Team $team
	 * @param array $d
	 * @return Member
	 */
	public function NewMember(Team $team, $d){ return new $this->MemberClass($team, $d); }
	
	/**
	 * @param Member $member
	 * @return MemberDetail
	 */
	public function NewMemberDetail(Member $member){
		return new $this->MemberDetailClass($member);
	}
	
	/**
	 * @return MemberList
	 */
	public function NewMemberList(){ return new $this->MemberListClass(); }
	
	public function IsAdminRole(){ return $this->modManager->IsAdminRole(); }
	public function IsWriteRole(){ return $this->modManager->IsWriteRole(); }
	public function IsViewRole(){ return $this->modManager->IsViewRole(); }
	
	public final function AJAX($d){
		$ret = new stdClass();
		$ret->result = $this->AJAXMethod($d);
		$users = TeamUserManager::ToAJAX();
		if (!empty($users)){
			$ret->users = $users;
		}
		
		if ($d->userconfigupdate){
			$ret->userconfig = $this->UserConfigToAJAX();
		}
		
		return $ret;
	}
	
	public function AJAXMethod($d){
		switch($d->do){
			case 'team':		return $this->TeamToAJAX($d->teamid);
			case 'teamsave':	return $this->TeamSave($d);
			case 'teamremove':	return $this->TeamRemove($d->teamid);
			case 'teamlist':	return $this->TeamListToAJAX($d->page, $d->memberid);
			
			case 'member':	 	return $this->MemberToAJAX($d->teamid, $d->memberid);
			case 'memberlist': 	return $this->MemberListToAJAX($d->teamid);
			case 'membersave': 	return $this->MemberSaveToAJAX($d->teamid, $d);
			
			case 'memberinviteact': return $this->MemberInviteAccept($d->teamid, $d->userid, $d->flag);
			
			case 'mynamesave': return $this->MyNameSave($d);
			
			case "event": return $this->EventToAJAX($d->teamid, $d->eventid);
			case "eventlist": return $this->EventListToAJAX($d->teamid);
			case "eventsave": return $this->EventSave($d->teamid, $d);
			case "eventremove": return $this->EventRemove($d->teamid, $d->eventid);
		}
		return null;
	}
	
	
	private $_teamCache = array();

	/**
	 * @param integer $teamid
	 * @return Team
	 */
	public function Team($teamid){
		if (!$this->IsViewRole()){ return null; }
		
		if (!empty($this->_teamCache[$teamid])){
			return $this->_teamCache[$teamid];
		}

		$row = TeamQuery::Team($this, $teamid);
		if (empty($row)){ return null; }
		
		if ($this->userid > 0){
			// сделан запрос авторизованным пользователем
			// нужно отметить что он смотрел эту группу
			TeamQuery::UserTeamView($this->db, $teamid);
		}
		
		$team = $this->NewTeam($row);
		
		$detail = $this->NewTeamDetail($team, $row);
		
		if ($team->role->IsAdmin()){
			$detail->inviteWaitCount = TeamQuery::MemberInviteWaitCountByTeam($this->db, $teamid);
		}
		$team->detail = $detail;
		
		$this->_teamCache[$teamid] = $team;
		
		return $team;
	}
	
	public function TeamToAJAX($teamid){
		$team = $this->Team($teamid);
		if (empty($team)){ return null; }
		
		TeamUserManager::AddId($team->authorid);
		
		$ret = new stdClass();
		$ret->team = $team->ToAJAX($other);
		
		return $ret;
	}
	
	/**
	 * 
	 * @param integer $page
	 * @param integer $memberid
	 * 
	 * @return TeamList
	 */
	public function TeamList($page = 1, $memberid = 0){
		if (!$this->IsViewRole()){ return null; }

		$rows = TeamQuery::TeamList($this, $page, $memberid);
		$list = $this->NewTeamList();
		
		while (($d = $this->db->fetch_array($rows))){
			$list->Add($this->NewTeam($d));
		}
		
		return $list;
	}
	
	public function TeamListToAJAX($page = 1, $limit = 15){
		$list = $this->TeamList($page, $limit);
		
		if (empty($list)){ return null; }
		
		$ret = new stdClass();
		$ret->teams = $list->ToAJAX();
		return $ret;
	}
	
	public function TeamSave($d){
		if (!$this->IsWriteRole()){ return null; }
	
		$d->id = intval($d->id);
	
		$utmf = Abricos::TextParser(true);
	
		$d->tl = $utmf->Parser($d->tl);
		$d->eml = $utmf->Parser($d->eml);
		$d->site = $utmf->Parser($d->site);
	
		$utm = Abricos::TextParser();
		$utm->jevix->cfgSetAutoBrMode(true);
	
		$d->dsc = $utm->Parser($d->dsc);
	
		if ($d->id == 0){ // добавление нового общества
				
			// TODO: необходимо продумать ограничение на создание сообществ
			$d->id = TeamQuery::TeamAppend($this->db, $this->modname, $this->userid, $d);
			if ($d->id == 0){
				return null;
			}
			TeamQuery::UserRoleUpdate($this->db, $d->id, $this->userid, 1, 1);
		} else {
			$team = new Team($d->id);
			if (!$team->role->IsAdmin()){ return null; }
			TeamQuery::TeamUpdate($this->db, $d);
		}
	
		TeamQuery::TeamMemberCountRecalc($this->db, $d->id);
	
		return $d->id;
	}
	
	public function TeamRemove($teamid){
		$team = $this->Team($teamid);
		if (is_null($team) || !$team->role->IsAdmin()){
			return null;
		}
		TeamQuery::TeamRemove($this->db, $teamid);
		return true;
	}
		
	/**
	 * 
	 * @param integer $teamid
	 * @param integer $memberid
	 * @param string $invite
	 * @return Member
	 */
	public function Member($teamid, $memberid){
		
		$team = $this->Team($teamid);
		if (empty($team)){ return null; }

		$row = TeamQuery::Member($this, $team, $memberid);
		if (empty($row)){ return null; }
		
		$member = $this->NewMember($team, $row);
		
		$member->detail = $this->NewMemberDetail($member);
		
		return $member;
	}
	
	public function MemberToAJAX($teamid, $memberid){
		$member = $this->Member($teamid, $memberid);

		if (empty($member)){ return null; }

		TeamUserManager::AddId($memberid);
		
		$ret = new stdClass();
		$ret->member = $member->ToAJAX();
		
		return $ret;
	}
	
	/**
	 * @param integer $teamid
	 * @return MemberList
	 */
	public function MemberList($teamid){
		$team = $this->Team($teamid);
		if (empty($team)){ return null; }
		
		$rows = TeamQuery::MemberList($this, $team);
		$list = $this->NewMemberList();
		while (($d = $this->db->fetch_array($rows))){
			$member = $this->NewMember($team, $d);
			$list->Add($this->NewMember($team, $d));
			
			TeamUserManager::AddId($member->id);
		}
		return $list;
	}
	
	public function MemberListToAJAX($teamid){
		$list = $this->MemberList($teamid);
		if (empty($list)){ return null; }
		
		$ret = new stdClass();
		$ret->members = $list->ToAJAX();
		
		return $ret;
	}
	
	public function MemberSave($teamid, $d){
		$team = $this->Team($teamid);
		
		if (!$team->role->IsAdmin()){ // текущий пользователь не админ => нет прав
			return null;
		}
		$d->id = intval($d->id);
		
		if ($d->id > 0){ 
			return null; // TODO: необходима реализация сохранения текущего пользователя
		}
		
		// приглашение участника в группу по email
		
		$d->email = strtolower($d->email);
		if (!Abricos::$user->GetManager()->EmailValidate($d->email)){
			return null;
		}
		Abricos::GetModule('team')->GetManager();
		$rd = TeamModuleManager::$instance->UserFindByEmail($d->email);
			
		if (empty($rd)){ return null; }
	
		if (!empty($rd->user) && $rd->user['id'] == $this->userid){
			if ($team->role->IsMember()){
				// уже участник группы
				// return null;
			}
			// добавляем себя в участники
			$d->id = $rd->user['id'];
		}else{
			// есть ли лимит на кол-во приглашений
			$ucfg = $this->UserConfig();
	
			if ($ucfg->inviteWaitLimit > -1 &&
					($ucfg->inviteWaitLimit - $ucfg->inviteWaitCount) < 1){
				// нужно подтвердить других участников, чтобы иметь возможность добавить еще
				return null;
			}
				
			if (empty($rd->user)){ // не найден такой пользователь в системе по емайл
				// сгенерировать учетку с паролем и выслать приглашение пользователю
				$invite = $this->MemberNewInvite($team, $d->email, $d->fnm, $d->lnm);
				if (is_null($invite)){
					return null;
				}
				$d->id = $invite->user['id'];
			}else{
				// выслать приглашение существующему пользователю
				$d->id = $rd->user['id'];

				$member = $this->Member($teamid, $rd->user['id']);
					
				if (!empty($member) && $member->role->IsMember()){
					// этот пользователь уже участник группы
					sleep(1);
					return null;
				}
				// приглашение существующего пользователя в группу
				$this->MemberInvite($team, $d->id);
			}
		}
		
		return $d->id;		
	}
	
	public function MemberSaveToAJAX($teamid, $d){
		$memberid = $this->MemberSave($teamid, $d);
		if (empty($memberid)){ return null; }
		
		$ret = $this->MemberToAJAX($teamid, $memberid);
		$ret->memberid = $memberid;
		return $ret;
	}
	
	/**
	 * Зарегистрировать нового пользователя
	 *
	 * @param string $email
	 * @param string $fname Имя
	 * @param string $lname Фамилия
	 */
	protected function MemberNewInvite(Team $team, $email, $fname, $lname){
	
		Abricos::GetModule('invite');
		$manInv = InviteModule::$instance->GetManager();
	
		// зарегистрировать пользователя (будет сгенерировано имя и пароль)
		$invite = $manInv->UserRegister($this->modname, $email, $fname, $lname);
	
		if ($invite->error == 0){
				
			// пометка пользователя флагом приглашенного
			// (система ожидает подтверждение от пользователя)
			TeamQuery::MemberInviteSetWait($this->db, $team->id, $invite->user['id'], $this->userid);
		}
	
		return $invite;
	}
	
	/**
	 * Пригласить существуюищего пользователя
	 * 
	 * @param Team $team
	 * @param integer $userid
	 */
	protected function MemberInvite(Team $team, $userid){
		$user = TeamUserManager::Get($userid);
	
		if (empty($user)){ return null; }
		// TODO: необходимо запрашивать разрешение на приглашение пользователя
		// пометка пользователя флагом приглашенного
		TeamQuery::MemberInviteSetWait($this->db, $team->id, $userid, $this->userid);
	
		return $userid;
	}
	
	public function MemberInviteAccept($teamid, $memberid, $flag){
		$member = $this->Member($teamid, $memberid);
		
		if (empty($member) || $member->id != $this->userid){ return null; }
		
		if ($flag){
			TeamQuery::MemberInviteSetAccept($this->db, $teamid, $memberid);
		}else{
			TeamQuery::MemberInviteSetReject($this->db, $teamid, $memberid);
		}
		
		return $this->MemberToAJAX($teamid, $memberid);
	}
	
	public function Event($teamid, $eventid){
		$team = $this->Team($teamid);
		if (empty($team)){ return null; }

		$row = TeamQuery::Event($this, $team, $eventid);
		if (empty($row)){ return null; }
		
		return new TeamEvent($team, $row);
	}
	
	public function EventToAJAX($teamid, $eventid){
		$event = $this->Event($teamid, $eventid);
		if (empty($event)){ return null; }
		
		$ret = new stdClass();
		$ret->event = $event->ToAJAX();
		
		return $ret;		
	}

	public function EventList($teamid){
		$team = $this->Team($teamid);
		if (empty($team)){ return null; }
	
		$list = new TeamEventList();
		$rows = TeamQuery::EventList($this, $team);
	
		while (($d = $this->db->fetch_array($rows))){
			$list->Add(new TeamEvent($team, $d));
		}
	
		return $list;
	}
	
	public function EventListToAJAX($teamid){
		$list = $this->EventList($teamid);
		if (empty($list)){
			return null;
		}
	
		$ret = new stdClass();
		$ret->events = $list->ToAJAX();
	
		return $ret;
	}
	
	public function EventSave($teamid, $d){
		$team = $this->Team($teamid);
		
		if (!$team->role->IsAdmin()){ // текущий пользователь не админ => нет прав
			return null;
		}
			
		$error = 0;
		$d->id = intval($d->id);
	
		$utmf = Abricos::TextParser(true);
		$d->tl = $utmf->Parser($d->tl);
	
		if ($d->id == 0){
			$d->id = TeamQuery::EventAppend($this->db, $teamid, $d);
		}else{
				
		}
	
		$ret = $this->EventListToAJAX($teamid);
		$ret->error = $error;
		$ret->eventid = $d->id;
	
		return $ret;
	}
	
	public function EventRemove($teamid, $eventid){
		$team = $this->Team($teamid);
		
		if (!$team->role->IsAdmin()){ // текущий пользователь не админ => нет прав
			return null;
		}
		
		TeamQuery::EventRemove($this->db, $teamid, $eventid);
		return true;
	}
		
	/**
	 * @return TeamUserConfig
	 */
	public function UserConfig(){
		if (!$this->IsViewRole()){
			return null;
		}
		return new $this->TeamUserConfigClass($this);
	}
	
	public function UserConfigToAJAX(){
		$ucfg = $this->UserConfig();
		if (is_null($ucfg)){
			return null;
		}
		return $ucfg->ToAJAX();
	}
	
	public function ToArray($rows, &$ids1 = "", $fnids1 = 'uid', &$ids2 = "", $fnids2 = '', &$ids3 = "", $fnids3 = ''){
		$ret = array();
		while (($row = $this->db->fetch_array($rows))){
			array_push($ret, $row);
			if (is_array($ids1)){
				$ids1[$row[$fnids1]] = $row[$fnids1];
			}
			if (is_array($ids2)){
				$ids2[$row[$fnids2]] = $row[$fnids2];
			}
			if (is_array($ids3)){
				$ids3[$row[$fnids3]] = $row[$fnids3];
			}
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
	
	public function MyNameSave($d){
		$utmf = Abricos::TextParser(true);
		$d->firstname = $utmf->Parser($d->firstname);
		$d->lastname = $utmf->Parser($d->lastname);
	
		TeamQuery::MyNameUpdate($this->db, $this->userid, $d);
	
		return $d;
	}
	
}


?>