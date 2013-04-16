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
		
		$this->role = $this->Manager()->NewTeamUserRole($this, Abricos::$user->id, $d);
	}
	
	/**
	 * @return TeamManager
	 */
	public abstract function Manager();

	public function ToAJAX(){
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
	
	public $team;
	
	/**
	 * Количество неподтвержденных приглашений
	 * @var integer
	 */
	public $inviteWaitCount = null;
	
	public function __construct(Team $team){
		$this->team = $team;
	}

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
	 * Пользователь участник группы
	 */
	public function IsMember(){
		return $this->_isMember == 1;
	}

	/**
	 * Пользовтель админ группы
	 */
	public function IsAdmin(){
		// глобальный админ всем админам админ
		if ($this->team->Manager()->IsAdminRole()){ return true; }
		
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

class TeamList extends TeamItemList {
}

class Member extends TeamItem {
	
	/**
	 * @var Team
	 */
	public $team = null;

	public $userName = '';
	public $firstName = '';
	public $lastName = '';
	public $avatar = '';
	
	/**
	 * Роль пользователя в группе
	 * @var TeamUserRole
	 */
	public $role;
	
	/**
	 * @var MemberDetail
	 */
	public $detail = null;

	public function __construct(Team $team, $d){
		parent::__construct($d);

		$this->id = $d['userid'];
		$this->team = $team;
		
		$this->role = $team->Manager()->NewTeamUserRole($team, $this->id, $d);

		$this->userName		= $d['username'];
		$this->firstName	= $d['firstname'];
		$this->lastName		= $d['lastname'];
		$this->avatar		= $d['avatar'];
	}

	public static function UserNameBuild($user){
		$firstname = !empty($user['fnm']) ? $user['fnm'] : $user['firstname'];
		$lastname = !empty($user['lnm']) ? $user['lnm'] : $user['lastname'];
		$username = !empty($user['unm']) ? $user['unm'] : $user['username'];
		return (!empty($firstname) && !empty($lastname)) ? $firstname." ".$lastname : $username;
	}
	
	public function ToAJAX(){
		$ret = parent::ToAJAX();
		
		$ret->unm = $this->userName;
		$ret->fnm = $this->firstName;
		$ret->lnm = $this->lastName;
		$ret->avt = $this->avatar;

		$ret->role = $this->role->ToAJAX();
		
		return $ret;
	}
}

class MemberDetail {
	public $member;
	
	public function __construct(Member $member){
		$this->member = $member;
	}
}

class MemberList extends TeamItemList {
	
	public function __construct(){
		parent::__construct();
	}

	public function Add(Member $item){
		parent::Add($item);
		
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
	protected $_ids = array();

	protected $isCheckDouble = false;

	public function __construct(){
		$this->_list = array();
		$this->_map = array();
	}

	public function Add(TeamItem $item = null){
		if (empty($item)){ return; }

		if ($this->isCheckDouble){
			$checkItem = $this->Get($item->id);
			if (!empty($checkItem)){
				return;
			}
		}

		$index = count($this->_list);
		$this->_list[$index] = $item;
		$this->_map[$item->id] = $index;
		
		array_push($this->_ids, $item->id);
	}
	
	/**
	 * Массив идентификаторов
	 */
	public function Ids(){
		return $this->_ids;
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