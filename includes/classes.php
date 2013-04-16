<?php 
/**
 * @package Abricos
 * @subpackage Team
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'classesman.php';

/**
 * Команда (сообщество, компания, клубы и т.п.)
 */
abstract class Team extends TeamItem {

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
	public $anyjoin = 0;
	public $memberCount = 0;

	/**
	 * Роль текущего пользователя в этой группе
	 * @var TeamUserRole
	 */
	public $role;
	
	/**
	 * @var TeamDetail
	 */
	public $detail = null;

	public function __construct($d){
		parent::__construct($d);
		
		$this->module		= strval($d['m']);
		$this->title		= strval($d['tl']);
		$this->authorid		= intval($d['auid']);
		$this->email		= strval($d['eml']);
		$this->descript		= strval($d['dsc']);
		$this->site			= strval($d['site']);
		$this->logo			= strval($d['logo']);
		$this->memberCount	= intval($d['mcnt']);
		
		// $this->role 		= 
		
	}
	
	/**
	 * @return TeamManager
	 */
	public abstract function Manager();

	public function ToAJAX($other = ''){
		$ret = parent::ToAJAX();
		$ret->m			= $this->module;
		$ret->auid		= $this->authorid;
		$ret->tl		= $this->title;
		$ret->eml		= $this->email;
		$ret->dsc		= $this->descript;
		$ret->site		= $this->site;
		$ret->logo		= $this->logo;
		$ret->anj		= $this->anyjoin;
		$ret->mcnt		= $this->memberCount;
		
		$ret->role		= $this->role->ToAJAX();

		if (!empty($this->detail)){
			$ret->dtl = $this->detail->ToAJAX();
		}

		return $ret;
	}
}

class TeamDetail {
	/**
	 * Количество неподтвержденных приглашений
	 * @var integer
	 */
	public $inviteWaitCount = null;

	public function ToAJAX(){
		$ret = new stdClass();
	
		if (!is_null($this->inviteWaitCount)){
			$ret->iwCount = $this->inviteWaitCount;
		}
		return $ret;
	}
}

/**
 * Роль участника к группе
 */
class TeamUserRole {

	public $userid;

	/**
	 * @var Team
	 */
	public $team;

	/**
	 * Участник группы
	 * @var boolean
	 */
	protected $_isMember = 0;

	/**
	 * Админ группы
	 * @var boolean
	 */
	protected $_isAdmin = 0;

	/**
	 * Приглашен, ожидает подтверждение
	 * @var boolean
	 */
	protected $_isInvite = 0;

	/**
	 * Послал запрос на вструпление, ожидает подтверждение
	 * @var boolean
	 */
	protected $_isJoinRequest = 0;

	/**
	 * Удален (до этого был участником группы)
	 * @var unknown_type
	 */
	protected $_isRemove = 0;

	public function __construct(Team $team, $userid, $d){
		$this->team				= $team;
		$this->userid			= $userid;

		$this->_isMember		= intval($d['ismember']);
		$this->_isAdmin			= intval($d['isadmin']);
		$this->_isInvite		= intval($d['isinvite']);
		$this->_isJoinRequest	= intval($d['isjoinrequest']);
		$this->_isRemove		= intval($d['isremove']);
		$this->_relUserId		= intval($d['reluserid']);
	}

	/**
	 * @return TeamManager
	 */
	public function Manager(){
		return TeamManager::$instance;
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
		if ($this->Manager()->IsAdminRole()){ return true; }
		if (!$this->IsMember()){ return false; }

		return $this->_isAdmin == 1;
	}

	public function ToAJAX(){

		$ret = new stdClass();
		$ret->ismbr = $this->_isMember;
		$ret->isadm = $this->_isAdmin;

		// Полные данные по ролям может получить админ группы или пользователь свои
		if ($this->userid == Abricos::$user->id || $this->team->role->IsAdmin()){
			$ret->isjrq = $this->_isJoinRequest;
			$ret->isinv = $this->_isInvite;
			$ret->ruid = $this->_relUserId;
		}

		return $ret;
	}

}

/**
 * Расширенный класс команды/группы
 */
class TeamExtended_OLD extends Team {


	public function MemberList(){
		$rows = TeamQuery::MemberList($this->db, $this->id, $this->member->IsAdmin());

		return new $this->manager->MemberListClass($this, $rows);
	}
	public function MemberLoad($memberid){
		$row = TeamQuery::Member($this->db, $this->id, $this->member->IsAdmin(), $memberid);

		if (empty($row)){
			return null;
		}

		return new $this->manager->MemberClass($this, $row);
	}
	
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

class TeamList extends TeamItemList {

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


class TeamItem {
	public $id;

	public function __construct($d){
		$this->id = intval($d['id']);
	}

	public function ToAJAX(){
		$ret = new stdClass();
		$ret->id = $this->id;
		return $ret;
	}
}

class TeamItemList {

	protected $_list = array();
	protected $_map = array();

	protected $isCheckDouble = false;

	public function __construct(){
		$this->_list = array();
		$this->_map = array();
	}

	public function Add(TeamItem $item = null){
		if (empty($item)){
			return;
		}

		if ($this->isCheckDouble){
			$checkItem = $this->Get($item->id);
			if (!empty($checkItem)){
				return;
			}
		}

		$index = count($this->_list);
		$this->_list[$index] = $item;
		$this->_map[$item->id] = $index;
	}

	public function Count(){
		return count($this->_list);
	}

	/**
	 * @param integer $index
	 * @return TeamItem
	 */
	public function GetByIndex($index){
		return $this->_list[$index];
	}

	/**
	 * @param integer $id
	 * @return TeamItem
	 */
	public function Get($id){
		$index = $this->_map[$id];
		return $this->_list[$index];
	}

	public function ToAJAX(){
		$list = array();
		$count = $this->Count();
		for ($i=0; $i<$count; $i++){
			array_push($list, $this->GetByIndex($i)->ToAJAX());
		}

		$ret = new stdClass();
		$ret->list = $list;

		return $ret;
	}
}

?>