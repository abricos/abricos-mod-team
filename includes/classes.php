<?php 
/**
 * @package Abricos
 * @subpackage Team
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'classesman.php';

class TeamConfig {

	/**
	 * @var TeamConfig
	 */
	public static $instance;
	
	/**
	 * Модерация новых сообществ
	 * 
	 * @var boolean
	 */
	public $moderationNewTeam = false;

	public function __construct($cfg){
		TeamConfig::$instance = $this;

		if (empty($cfg)){
			$cfg = array();
		}

		if (isset($cfg['moderationNewTeam'])){
			$this->moderationNewTeam = $cfg['moderationNewTeam'];
		}
	}
}

/**
 * Билдер ссылок
 * 
 * http://host/team/ - список сообществ
 * http://host/team/page[n]/ - страница списка сообществ
 * http://host/team/by/ - список модулей сообществ
 * http://host/team/by/[modname]/ - список сообществ модуля
 * http://host/team/t[teamid]/ - сообщество
 * http://host/team/t[teamid]/member/ - список участников
 * http://host/team/t[teamid]/member/by/ - список модулей участников
 * http://host/team/t[teamid]/member/by/[modname]/ - список участников в модуле
 * http://host/team/t[teamid]/member/[memberid]/ - участник со страницами модулей
 * http://host/team/t[teamid]/member/by/[modname]/[memberid]/ - просмотр участника в модуле
 */
class TeamNavigator {
	
	public $isURL;
	
	public function __construct($isURL = false){
		$this->isURL = $isURL;
	}
	
	public function URL(){
		if ($this->isURL){
			return Abricos::$adress->host."/team/";
		}
		return "/team/";
	}
	
	public function TeamList($modname = ''){
		if (empty($modname)){
			return $this->URL();
		}else{
			return $this->URL()."by/".$modname."/";
		}
	}
	
	public function TeamView($teamid){
		return $this->URL()."t".intval($teamid)."/";
	}
	
	/*
	public function MemberList($teamid, $modname = ''){
		if (empty($modname)){
			return $this->TeamView($teamid)."member/";
		}else{
			return $this->TeamView($teamid)."member/by/".$modname."/";
		}
	}
	
	public function MemberView($teamid, $memberid, $modname = ''){
		$memberid = intval($memberid);
		if (empty($modname)){
			return $this->TeamView($teamid)."member/m".$memberid."/";
		}else{
			return $this->TeamView($teamid)."member/by/".$modname."/m".$memberid."/";
		}
	}
	/**/
}


/**
 * Сообщество (облако, компания, клубы и т.п.)
 */
abstract class Team extends TeamItem {

	/**
	 * Имя управляющего модуля 
	 * @var string
	 */
	public $module = '';
	
	public $parentModule = '';
	
	/**
	 * Тип сообещства
	 * @var string
	 */
	public $type = '';
	
	public $title = '';
	public $authorid = 0;
	public $email = '';
	public $descript = '';
	public $site = '';
	public $logo = '';
	public $anyjoin = 0;
	public $memberCount = 0;
	
	/**
	 * Статус модерации. 1 - ожидает модерации
	 * @var boolean
	 */
	public $moderStatus = 0;

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
		$this->type			= strval($d['tp']);
		$this->title		= strval($d['tl']);
		$this->authorid		= intval($d['auid']);
		$this->email		= strval($d['eml']);
		$this->descript		= strval($d['dsc']);
		$this->site			= strval($d['site']);
		$this->logo			= strval($d['logo']);
		$this->memberCount	= intval($d['mcnt']);
		$this->moderStatus	= intval($d['mdr']);
		
		$this->role = $this->Manager()->NewTeamUserRole($this, Abricos::$user->id, $d);
		
		$this->parentModule = $this->Manager()->parentModuleName;
	}
	
	/**
	 * @return TeamManager
	 */
	public abstract function Manager();
	
	/**
	 * True - ожидает модерацию
	 * @return boolean
	 */
	public function IsModeration(){
		return $this->moderStatus;
	}

	public function ToAJAX(){
		$ret = parent::ToAJAX();
		$ret->m			= $this->module;
		$ret->pm		= $this->parentModule;
		$ret->tp		= $this->type;
		$ret->auid		= $this->authorid;
		$ret->tl		= $this->title;
		$ret->eml		= $this->email;
		$ret->dsc		= $this->descript;
		$ret->site		= $this->site;
		$ret->logo		= $this->logo;
		$ret->anj		= $this->anyjoin;
		$ret->mcnt		= $this->memberCount;
		$ret->mdr		= $this->ismoder ? 1 : 0;
		$ret->role		= $this->role->ToAJAX();

		if (!empty($this->detail)){
			$ret->dtl = $this->detail->ToAJAX();
		}

		return $ret;
	}
}

/**
 * Детальная информация сообщества
 */
class TeamDetail {
	
	/**
	 * @var Team
	 */
	public $team;
	
	/**
	 * Количество неподтвержденных приглашений
	 * @var integer
	 */
	// public $inviteWaitCount = null;
	
	public function __construct(Team $team, $d){
		$this->team = $team;
	}

	public function ToAJAX(){
		$ret = new stdClass();
	
		/*
		if (!is_null($this->inviteWaitCount)){
			$ret->iwCount = $this->inviteWaitCount;
		}
		/**/
		return $ret;
	}
}

/**
 * Роль участника в сообществе
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
	 * @var boolean
	 */
	protected $_isRemove = 0;
	
	/**
	 * Виртуальный пользователь
	 * @var boolean
	 */
	protected $_isVirtual = 0;

	public function __construct(Team $team, $userid, $d){
		$this->team				= $team;
		$this->userid			= $userid;

		$this->_isMember		= intval($d['ismember']);
		$this->_isAdmin			= intval($d['isadmin']);
		$this->_isInvite		= intval($d['isinvite']);
		$this->_isJoinRequest	= intval($d['isjoinrequest']);
		$this->_isRemove		= intval($d['isremove']);
		$this->_relUserId		= intval($d['reluserid']);
		$this->_isVirtual		= intval($d['isvirtual']);
	}
	
	/**
	 * Пользователь участник сообщества
	 */
	public function IsMember(){
		return $this->_isMember == 1;
	}

	/**
	 * Пользовтель админ сообещства
	 */
	public function IsAdmin(){
		// глобальный админ всем админам админ
		if ($this->team->Manager()->IsAdminRole()){ return true; }
		
		if (!$this->IsMember()){ return false; }

		return $this->_isAdmin == 1;
	}
	
	/**
	 * Виртуальный участник сообщества
	 */
	public function IsVirtual(){
		return $this->_isVirtual == 1;
	}

	public function ToAJAX(){

		$ret = new stdClass();
		$ret->ismbr = $this->_isMember;
		$ret->isadm = $this->_isAdmin;
		$ret->isvrt = $this->_isVirtual;

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

class TeamUser extends TeamItem {
	
	public $userName = '';
	public $firstName = '';
	public $lastName = '';
	public $avatar = '';
	public $isVirtual = false;

	public function __construct($d){
		parent::__construct($d);
		
		$this->userName		= strval($d['unm']);
		$this->firstName	= strval($d['fnm']);
		$this->lastName		= strval($d['lnm']);
		$this->avatar		= strval($d['avt']);
		$this->isVirtual	= intval($d['vrt']) == 1;
	}
	
	public function UserNameBuild(){
		return (!empty($this->firstName) && !empty($this->lastName)) ? 
			$this->firstName." ".$this->lastName : $this->userName;
	}
	
	public function ToAJAX(){
		$ret = parent::ToAJAX();
	
		$ret->unm = $this->userName;
		$ret->fnm = $this->firstName;
		$ret->lnm = $this->lastName;
		$ret->avt = $this->avatar;
		$ret->vrt = $this->isVirtual ? 1 : 0;
	
		return $ret;
	}
}

class TeamUserList extends TeamItemList {
	/**
	 * @return TeamUser
	 */
	public function Get($id){ return parent::Get($id); }
	
	/**
	 * @return TeamUser
	 */
	public function GetByIndex($index){ return parent::GetByIndex($index); }
}

class TeamUserManager {

	/**
	 * @var TeamUserList
	 */
	private static $list = null;
	private static $_ids = array();
	
	public static function Clear(){
		TeamUserManager::$_ids = array();
	}
	
	public static function AddId($userid){
		$ids = &TeamUserManager::$_ids;
		if (!empty($ids[$userid])){ return; }
		
		$o = new stdClass();
		$o->load = false;
		$ids[$userid] = $o;
	}
	
	private static function LoadList(){
		if (empty(TeamUserManager::$list)){
			TeamUserManager::$list = new TeamUserList();
		}
		
		$ids = &TeamUserManager::$_ids;
		$arr = array();

		foreach ($ids as $key => $value){
			if ($ids[$key]->load){
				continue;
			}
			$ids[$key]->load = true;
			array_push($arr, $key);
		}
		
		if (count($arr) == 0){ return; }
		$rows = TeamQuery::UserByIds(Abricos::$db, $arr);
		while (($d =Abricos::$db->fetch_array($rows))){
			TeamUserManager::$list->Add(new TeamUser($d));
		}
	}
	
	/**
	 * @return TeamUser
	 */
	public static function Get($userid){
		if (empty(TeamUserManager::$list)){
			TeamUserManager::$list = new TeamUserList();
		}

		$list = TeamUserManager::$list;
		$user = $list->Get($userid);
		if (empty($user)){
			TeamUserManager::AddId($userid);
			TeamUserManager::LoadList();
			$user = $list->Get($userid);
		}
		return $user;
	}
	
	public static function ToAJAX(){
		if (count(TeamUserManager::$_ids) == 0){
			return null;
		}
		TeamUserManager::LoadList();
		$ret = TeamUserManager::$list->ToAJAX();
		$ret = $ret->list;
		return $ret;
	}
}

/**
 * Информация о приложении для сообщества
 */
class TeamAppInfo extends AbricosItem {
	
	private static $idCounter = 1;
	
	/**
	 * Имя модуля
	 * @var string
	 */
	public $moduleName;
	
	/**
	 * Имя приложения
	 * @var string
	 */
	public $name;
	
	/**
	 * Имя виджета
	 * @var string
	 */
	public $widget;
	
	/**
	 * Название приложения
	 * @var string
	 */
	public $title;
	
	/**
	 * Использовать приложение только для определенного модуля
	 * @var string
	 */
	public $onlyModule = '';
	
	public $parent = '';
	
	public function __construct($moduleName, $name = '', $widget = '', $title = '', $onlyModule = '', $parent = ''){
		$this->id = TeamAppInfo::$idCounter++;
		
		if (is_array($moduleName)){
			$a = $moduleName;
			$moduleName = $a['moduleName'];
			$name = $a['name'];
			$widget = $a['widget'];
			$title = $a['title'];
			$onlyModule = $a['onlyModule'];
			$parent = $a['parent'];
		}
		
		$this->moduleName = $moduleName;
		
		if (empty($name)){ $name = $moduleName; }
		$this->name = $name;
		
		if (empty($widget)){ $widget = $moduleName; }
		$this->widget = strval($widget);
		
		$this->title = strval($title);
		
		$this->onlyModule = strval($onlyModule);
		$this->parent = strval($parent);
	}
	
	public function ToAJAX(){
		$ret = parent::ToAJAX();
		$ret->mnm = $this->moduleName;
		$ret->nm = $this->name;
		$ret->w = $this->widget;
		$ret->tl = $this->title;
		$ret->pnm = $this->parent;
		return $ret;
	}
}

class TeamAppInfoList extends AbricosList { }

class TeamTypeInfo extends AbricosItem {
	
	private static $idCounter = 1;
	
	/**
	 * Имя типа (соответсвует имени модуля)
	 * @var string
	 */
	public $name;
	
	/**
	 * Имя модуля сообещства для которого определяется этот тип 
	 * @var string
	 */
	public $teamModName;
	public $title;

	public function __construct($name, $teamModName, $title = ''){
		$this->id = TeamTypeInfo::$idCounter++;
		$this->name = $name;
		$this->teamModName = $teamModName;
		
		if (empty($title)){ $title = $name; }
		$this->title = $title;
	}
	
	public function ToAJAX(){
		$ret = parent::ToAJAX();
		$ret->nm = $this->name;
		$ret->tnm = $this->teamModName;
		$ret->tl = $this->title;
		return $ret;
	}
}

class TeamTypeInfoList extends  AbricosList {}

class TeamInitData {
	
	/**
	 * Приложения для сообществ
	 * @var TeamAppInfoList
	 */
	public $appList;
	
	/**
	 * Типы сообществ
	 * @var TeamTypeInfoList
	 */
	public $typeList;
	
	/**
	 * @var TeamManager
	 */
	public $manager;
	
	public function __construct(TeamManager $man){

		$this->manager = $man;
		$this->appList = new TeamAppInfoList();
		
		// зарегистрировать все модули
		Abricos::$instance->modules->RegisterAllModule();
		$modules = Abricos::$instance->modules->GetModules();
		
		// опросить каждый модуль на наличие приложения для сообщества
		// сначало модуль родитель, затем все остальные модули
		$module = $man->modManager->module;
		if (method_exists($module, 'Team_GetAppInfo')){
			$appInfo = $module->Team_GetAppInfo();
			$this->RegApp($appInfo);
		}
		foreach ($modules as $name => $module){
			if ($name == $man->moduleName){ continue; }
			if (!method_exists($module, 'Team_GetAppInfo')){
				continue;
			}
			$appInfo = $module->Team_GetAppInfo();
			$this->RegApp($appInfo);
		}
		
		// зарегистрировать типы сообществ
		$this->typeList = new TeamTypeInfoList();
		foreach ($modules as $name => $module){
			if (!method_exists($module, 'Team_GetTypeInfo')){
				continue;
			}
			$typeInfo =  $module->Team_GetTypeInfo();
			$this->RegType($typeInfo);
		}
	}
	
	public function RegApp($appInfo){
		if (is_array($appInfo)){
			foreach($appInfo as $item){
				$this->RegApp($item);
			}
		}else if ($appInfo instanceof TeamAppInfo){
			
			$moduleName = $this->manager->modManager->module->name;
			
			if (!empty($appInfo->onlyModule) 
					&& $appInfo->onlyModule != $moduleName){
				return;
			}
			$this->appList->Add($appInfo);
		}
	}
	
	public function RegType($typeInfo){
		$moduleName = $this->manager->modManager->module->name;
		
		if ($typeInfo instanceof TeamTypeInfo){
			if ($typeInfo->teamModName != $moduleName){ return; }
			$this->typeList->Add($typeInfo);
		}
	}
	
	public function ToAJAX(){
		$ret = new stdClass();
		
		$apps = $this->appList->ToAJAX();
		$ret->apps = $apps->list;
		
		$types = $this->typeList->ToAJAX();
		$ret->types = $types->list;
		
		return $ret;
	}
}

class TeamUserConfig {

	/**
	 * Количество всего неподтвержденных приглашений
	 * @var integer
	 */
	// public $inviteWaitCount = 0;

	/**
	 * Лимит неподтвержденных приглашений
	 * @var integer
	 */
	// public $inviteWaitLimit = 0;


	public function __construct(TeamManager $man){
		$userid = Abricos::$user->id;
		$db = $man->db;

		if ($userid == 0){
			return;
		}

		// $this->inviteWaitCount = TeamQuery::MemberInviteWaitCountByUser($db, $userid);
		// $this->inviteWaitLimit = $man->IsAdminRole() ? -1 : 5;
	}

	public function ToAJAX(){
		$ret = new stdClass();
		// $ret->iwCount = $this->inviteWaitCount;
		// $ret->iwLimit = $this->inviteWaitLimit;
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

	public function Add($item = null){
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