<?php 
/**
 * @version $Id: manager.php 1311 2012-12-13 09:54:18Z roosit $
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
	public $TeamExtendedClass	= TeamExtended;
	
	/**
	 * @var TeamList
	 */
	public $TeamListClass		= TeamList;
	
	public $MemberClass			= Member;
	public $MemberExtendedClass = MemberExtended;
	public $MemberListClass		= MemberList;
	
	public $TeamUserConfigClass	= TeamUserConfig;
	
	/**
	 * @param TeamModuleManager $modManager
	 */
	public function __construct(Ab_ModuleManager $modManager){
		$this->modManager = $modManager;
		$this->modname = $modManager->module->name;
		$this->db = $modManager->db;
		$this->user = $modManager->user;
		$this->userid = $modManager->userid;
	}
	
	public function IsAdminRole(){ return $this->modManager->IsAdminRole(); }
	public function IsWriteRole(){ return $this->modManager->IsWriteRole(); }
	public function IsViewRole(){ return $this->modManager->IsViewRole(); }
	
	public final function AJAX($d){
		$ret = new stdClass();
		$ret->result = $this->AJAXMethod($d);
		
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
			case 'membersave': 	return $this->MemberSave($d->teamid, $d);
		}
		return null;
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
	
	public function TeamSave($d){
		if (!$this->IsWriteRole()){ return null; }
		
		$d->id = intval($d->id);
		
		$utmf = Abricos::TextParser(true);
		
		$d->tl =  $utmf->Parser($d->tl);
		$d->eml =  $utmf->Parser($d->eml);
		$d->site =  $utmf->Parser($d->site);
		
		$utm = Abricos::TextParser();
		$utm->jevix->cfgSetAutoBrMode(true);
		
		$d->dsc =  $utm->Parser($d->dsc);
		
		if ($d->id == 0){ // добавление нового общества
			
			// TODO: необходимо продумать ограничение на создание сообществ
			$d->id = TeamQuery::TeamAppend($this->db, $this->modname, $this->userid, $d);
			if ($d->id == 0){
				return null;
			}
			TeamQuery::UserRoleUpdate($this->db, $d->id, $this->userid, 1, 1);
		} else {
			$team = new Team($d->id);
			if (!$team->member->IsAdmin()){
				return null;
			}
			TeamQuery::TeamUpdate($this->db, $d);
		}
		
		TeamQuery::TeamMemberCountRecalc($this->db, $d->id);
		
		return $d->id;		
	}
	
	public function TeamRemove($teamid){
		$team = $this->Team($teamid);
		if (is_null($team) || !$team->member->IsAdmin()){
			return null;
		}
		TeamQuery::TeamRemove($this->db, $teamid);
		return true;
	}
	
	/**
	 * @param integer $teamid
	 * @return TeamExtended
	 */
	public function Team($teamid){
		if (!$this->IsViewRole()){ return null; }

		$row = TeamQuery::Team($this->db, $this->modname, $teamid);
		if (empty($row)){ return null; }
		$team = new $this->TeamClass($this, $row);
		
		$team = $team->Extend();
		
		return $team;
	}
	
	public function TeamToAJAX($teamid, $other = ''){
		$team = $this->Team($teamid);
		if (is_null($team)){ return null; }
		
		return $team->ToAJAX($other);
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

		$rows = TeamQuery::TeamList($this->db, $this->modname, $page, $memberid);
		$list = new $this->TeamListClass($this, $rows);
		
		return $list;
	}
	
	public function TeamListToAJAX($page = 1, $limit = 15){
		$teamList = $this->TeamList($page, $limit);
		
		if (is_null($teamList)){ return null; }
		
		return $teamList->ToAJAX();
	}
	
	/**
	 * @param integer $teamid
	 * @param integer $memberid
	 * @return MemberExtended
	 */
	public function Member($teamid, $memberid){

		$team = $this->Team($teamid);
		
		if (is_null($team)){ return null; }

		$member = $team->MemberLoad($memberid);
		
		if (is_null($member)){ return null; }
		$member = $member->Extend();
		
		return $member;
	}
	
	public function MemberToAJAX($teamid, $memberid){

		$member = $this->Member($teamid, $memberid);

		if (is_null($member)){ return null; }
		return $member->ToAJAX();
	}
	
	/**
	 * @param integer $teamid
	 * @return MemberList
	 */
	public function MemberList($teamid){
		$team = $this->Team($teamid);
		if (is_null($team)){ return null; }

		return $team->MemberList();
	}
	
	public function MemberListToAJAX($teamid){
		$list = $this->MemberList($teamid);
		if (is_null($list)){ return null; }
		
		return $list->ToAJAX();
	}
	
	public function MemberSave($teamid, $d){
		$team = $this->Team($teamid);
		if (is_null($team)){
			return null;
		}
		
		return $team->MemberSave($d);
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
}


class Team {

	/**
	 * @var TeamManager
	 */
	public $manager;

	/**
	 * Идентификатор группы
	 * @var integer
	 */
	public $id = 0;

	/**
	 * Имя управляющего модуля 
	 * @var string
	 */
	public $module = '';
	public $title = '';
	public $authorid = 0;
	public $email = '';
	public $descript = '';
	public $site = '';
	public $logo = '';
	public $memberCount = 0;

	/**
	 * Текущий пользователь (его роль в группе)
	 * @var Member
	 */
	public $member = null;

	public function __construct(TeamManager $manager, $d){

		$this->manager = $manager;

		$this->id = $d['teamid'];

		$this->module		= $d['module'];
		$this->title		= $d['title'];
		$this->authorid		= $d['authorid'];
		$this->email		= $d['email'];
		$this->descript		= $d['descript'];
		$this->site			= $d['site'];
		$this->logo			= $d['logo'];
		$this->memberCount	= $d['membercount'];

		$this->member = new $manager->MemberClass($this, $d);
	}

	/**
	 * @return TeamExtended
	 */
	public function Extend(){
		return new $this->manager->TeamExtendedClass($this);
	}
	
	public function CloneToExtend(TeamExtended $teamex){
		$objs = get_class_vars(get_class($this));
		foreach($objs as $key => $val){
			$teamex->$key = $this->$key;
		}
	}

	public function ToAJAX($other = ''){

		$ret = new stdClass();
		$ret->id		= $this->id;
		$ret->m			= $this->module;
		$ret->auid		= $this->authorid;
		$ret->tl		= $this->title;
		$ret->eml		= $this->email;
		$ret->dsc		= $this->descript;
		$ret->site		= $this->site;
		$ret->logo		= $this->logo;
		$ret->anj		= anyjoin;
		$ret->mbrcnt	= $this->memberCount;

		$ret->member = $this->member->ToAJAX();

		return $ret;
	}
}

/**
 * Расширенный класс команды/группы
 */
class TeamExtended extends Team {

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
	 * Количество неподтвержденных приглашений
	 * @var integer
	 */
	public $inviteWaitCount = 0;

	public function __construct(Team $team){
		$team->CloneToExtend($this);

		$man = $team->manager;
		$this->db = $man->db;
		$this->user = $man->user;
		$this->userid = $man->userid;

		if ($this->userid > 0){
			// сделан запрос авторизованным пользователем
			// нужно отметить что он смотрел эту группу
			TeamQuery::UserTeamView($this->db, $this->userid);
		}
		
		$this->member = $this->member->Extend();
		
		if ($this->member->IsAdmin()){
			$this->inviteWaitCount = TeamQuery::MemberInviteWaitCountByTeam($this->db, $this->id);
		}
		$this->OnLoad();
	}

	public function OnLoad(){ }
	public function Extend(){ return $this; }

	public function ToAJAX($other = ''){
		$ret = parent::ToAJAX($other = '');
		$ret->extended = true;

		if ($this->member->IsAdmin()){
			$ret->iwCount = $this->inviteWaitCount;
		}
		return $ret;
	}

	/**
	 * @return MemberList
	 */
	public function MemberList(){
		$rows = TeamQuery::MemberList($this->db, $this->id, $this->member->IsAdmin());

		return new $this->manager->MemberListClass($this, $rows);
	}

	/**
	 * @param integer $memberid
	 * @return Member
	 */
	public function MemberLoad($memberid){
		$row = TeamQuery::Member($this->db, $this->id, $this->member->IsAdmin(), $memberid);

		if (empty($row)){
			return null;
		}

		return new $this->manager->MemberClass($this, $row);
	}
	
	/**
	 * Добавление/сохранение участника группы
	 * @param object $d
	 */
	public function MemberSave($d){
		if (!$this->member->IsAdmin()){ // текущий пользователь не админ => нет прав
			return null;
		}
		
		if ($d->id == 0){ // приглашение участника в группу по email
		
			$d->email = strtolower($d->email);
			if (!Abricos::$user->GetManager()->EmailValidate($d->email)){
				return null;
			}
			Abricos::GetModule('team')->GetManager();
			$rd = TeamModuleManager::$instance->UserFindByEmail($d->email);
			
			if (is_null($rd)){ return null; }

			if (!is_null($rd->user) && $rd->user['id'] == $this->member->id){
				if ($this->member->IsMember()){
					// уже участник группы
					// return null;
				}
				// добавляем себя в участники
				$d->id = $rd->user['id'];
			}else{
				// есть ли лимит на кол-во приглашений
				$ucfg = $this->manager->UserConfig();

				if ($ucfg->inviteWaitLimit > -1 && 
						($ucfg->inviteWaitLimit - $ucfg->inviteWaitCount) < 1){
					// нужно подтвердить других участников, чтобы иметь возможность добавить еще
					return null;
				}
					
				if (is_null($rd->user)){ // не найден такой пользователь в системе по емайл
					// сгенерировать учетку с паролем и выслать приглашение пользователю
					$invite = $this->MemberNewInvite($d->email, $d->fnm, $d->lnm);
					if (is_null($invite)){ return null; }
					$d->id = $invite->user['id'];
				}else{
					// выслать приглашение существующему пользователю
					$d->id = $rd->user['id'];
					print_r($rd);
					$member = $this->MemberLoad($rd->user['id']);
					
					if (!is_null($member) && $member->IsMember()){
						// этот пользователь уже участник группы
						sleep(1);
						return null;
					}
					// приглашение существующего пользователя в группу
					$this->MemberInvite($d->id);
				}
			}
		}
		return $d->id;
	}
	
	/**
	 * Зарегистрировать нового пользовател
	 * 
	 * @param string $email
	 * @param string $fname Имя
	 * @param string $lname Фамилия
	 */
	protected function MemberNewInvite($email, $fname, $lname){
	
		Abricos::GetModule('invite');
		$manInv = InviteModule::$instance->GetManager();
	
		// зарегистрировать пользователя (будет сгенерировано имя и пароль)
		$invite = $manInv->UserRegister($this->module, $email, $fname, $lname);
	
		if ($invite->error == 0){
			
			// пометка пользователя флагом приглашенного
			// (система ожидает подтверждение от пользователя)
			TeamQuery::MemberInviteSetWait($this->db, $this->id, $invite->user['id'], $this->userid);
		}
		
		return $invite;
	}
	
	protected function MemberInvite($userid){
		$user = UserQueryExt::User($this->db, $userid);
		
		if (empty($user)){
			return null;
		}
		// TODO: необходимо запрашивать разрешение на приглашение пользователя
		// пометка пользователя флагом приглашенного
		TeamQuery::MemberInviteSetWait($this->db, $this->id, $userid, $this->userid);
		
		return $userid;
	} 
		

}

class TeamList {

	/**
	 * @var TeamManager
	 */
	public $manager;

	public $list = array();

	public function __construct(TeamManager $manager, $rows = null){
		$this->manager = $manager;

		if (!is_null($rows)){
			$this->Update($rows);
		}
	}

	public function Update($rows){
		$man = $this->manager;
		$list = array();
		while (($row = $man->db->fetch_array($rows))){
			array_push($list, new $man->TeamClass($man, $row));
		}
		$this->list = $list;
	}

	public function ToAJAX(){
		$ret = array();
		for ($i=0;$i<count($this->list); $i++){
			array_push($ret, $this->list[$i]->ToAJAX());
		}
		return $ret;
	}
}

class Member {
	
	/**
	 * Группа
	 *
	 * @var Team
	 */
	public $team = null;

	/**
	 * Идентификатор участника
	 * @var integer
	 */
	public $id = 0;

	public $userName = '';
	public $firstName = '';
	public $lastName = '';
	public $avatar = '';

	protected $_isMember = 0;
	protected $_isAdmin = 0;
	protected $_isInvite = 0;
	protected $_isJoinRequest = 0;
	protected $_isRemove = 0;

	protected $_relUserId = 0;

	public function __construct(Team $team, $d){
		$this->team = $team;

		if (empty($d)){
			return;
		}

		$this->id				= $d['userid'];
		$this->_isMember		= $d['ismember'];
		$this->_isAdmin			= $d['isadmin'];
		$this->_isInvite		= $d['isinvite'];
		$this->_isJoinRequest	= $d['isjoinrequest'];
		$this->_isRemove		= $d['isremove'];
		$this->_relUserId		= $d['reluserid'];

		if (!empty($d['username'])){
			$this->userName		= $d['username'];
			$this->firstName	= $d['firstname'];
			$this->lastName		= $d['lastname'];
			$this->avatar		= $d['avatar'];
		} else if ($this->id > 0 && $this->id == Abricos::$user->id){
			$info = Abricos::$user->info;
				
			$this->userName		= $info['username'];
			$this->firstName	= $info['firstname'];
			$this->lastName		= $info['lastname'];
			$this->avatar		= $info['avatar'];
		}
	}

	public static function UserNameBuild($user){
		$firstname = !empty($user['fnm']) ? $user['fnm'] : $user['firstname'];
		$lastname = !empty($user['lnm']) ? $user['lnm'] : $user['lastname'];
		$username = !empty($user['unm']) ? $user['unm'] : $user['username'];
		return (!empty($firstname) && !empty($lastname)) ? $firstname." ".$lastname : $username;
	}
	
	/**
	 * Пользователь участник группы
	 */
	public function IsMember(){
		return $this->_isMember == 1;
	}

	/**
	 * Пользовтель админ группы
	 */
	public function IsAdmin(){
		if ($this->team->manager->IsAdminRole()){
			return true;
		}

		if (!$this->IsMember()){
			return false;
		}

		return $this->_isAdmin == 1;
	}

	public function ToAJAX(){
		$team = $this->team;

		$ret = new stdClass();
		$ret->id = $this->id;
		$ret->ismbr = $this->_isMember;
		$ret->isadm = $this->_isAdmin;

		/*
		 * Полные данные по ролям может получить админ группы или пользователь свои
		*/
		if ($team->member->id == $this->id || $team->member->IsAdmin()){
			$ret->isjrq = $this->_isJoinRequest;
			$ret->isinv = $this->_isInvite;
			$ret->ruid = $this->_relUserId;
		}
		if ($this->id > 0){
			$ret->unm = $this->userName;
			$ret->fnm = $this->firstName;
			$ret->lnm = $this->lastName;
			$ret->avt = $this->avatar;
		}

		return $ret;
	}

	/**
	 * @return MemberExtended
	 */
	public function Extend(){
		return new $this->team->manager->MemberExtendedClass($this);
	}
	
	public function CloneToExtend(MemberExtended $memberex){
		$objs = get_class_vars(get_class($this));
		foreach($objs as $key => $val){
			$memberex->$key = $this->$key;
		}
	}

}

class MemberExtended extends Member {
	/**
	 * @var Ab_Database
	 */
	public $db;

	public function __construct(Member $member){
		
		$member->CloneToExtend($this);
		
		/*
		$objs = get_object_vars($member);
		foreach($objs as $key => $val){
			$this->$key = $val;
			print_r(array($key, @($val."")));
		}/**/
		
		$this->db = $member->team->manager->db;

		$this->OnLoad();
	}

	public function OnLoad(){
	}
	public function Extend(){
		return $this;
	}

	public function ToAJAX(){
		$ret = parent::ToAJAX();
		$ret->exteded = true;
		return $ret;
	}

}

class MemberList {

	/**
	 * @var Team
	 */
	public $team;

	public $data = array();

	public function __construct(Team $team, $rows = null){
		$this->team = $team;

		if (!is_null($rows)){
			$this->Update($rows);
		}
	}

	public function Update($rows){
		$man = $this->team->manager;
		while (($row = $man->db->fetch_array($rows))){
			$member = new $man->MemberClass($this->team, $row);
			$this->data[$member->id] = $member;
		}
	}

	public function Get($id){
		return $this->data[$id];
	}

	public function ToAJAX(){
		$ret = array();
		foreach($this->data as $id => $member){
			array_push($ret, $member->ToAJAX());
		}
		return $ret;
	}
}

class TeamUserConfig {

	/**
	 * Количество всего неподтвержденных приглашений
	 * @var integer
	 */
	public $inviteWaitCount = 0;

	/**
	 * Лимит неподтвержденных приглашений
	 * @var integer
	 */
	public $inviteWaitLimit = 0;


	public function __construct(TeamManager $man){
		$userid = Abricos::$user->id;
		$db = $man->db;

		if ($userid == 0){
			return;
		}

		$this->inviteWaitCount = TeamQuery::MemberInviteWaitCountByUser($db, $userid);
		$this->inviteWaitLimit = $man->IsAdminRole() ? -1 : 5;
	}

	public function ToAJAX(){
		$ret = new stdClass();
		$ret->iwCount = $this->inviteWaitCount;
		$ret->iwLimit = $this->inviteWaitLimit;
		return $ret;
	}
}


?>