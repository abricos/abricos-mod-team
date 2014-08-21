<?php 
/**
 * @package Abricos
 * @subpackage Team
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */
require_once 'dbquery.php';

class TeamDebugLogger {
	
	public $enable = false;
	public $log = array();
	
	public function __construct(){
		$this->enable = Abricos::$config['Misc']['develop_mode'];
	}
	
	public function Add($text){
		if ($this->enable){
			array_push($this->log, $text);
		}
	}
}

class TeamManager {
	
	/**
	 * @var TeamDebugLogger
	 */
	public static $log;
	
	/**
	 * @var Ab_ModuleManager
	 */
	public $modManager = null;

	/**
	 * Имя управляющего модуля
	 * @var string
	 */
	public $moduleName = '';
	
	/**
	 * Имя модуля родителя
	 * @var string
	 */
	public $parentModuleName = '';

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
	
	public $TeamNavigatorClass	= TeamNavigator;
	
	/**
	 * Информация о расширенной таблицы ролей пользовтеля
	 * Например для списка спортсменов:
	 * $this->fldExtTeamUserRole['sportsman_userrole'] = "issportsman,postid"; 
	 * 
	 * sportsman_userrole - таблица расширения в базе
	 * issportsman,postid - перечень полей в ней
	 */
	public $fldExtTeamUserRole = array();
	
	public $fldExtTeamDetail = array();
	
	/**
	 * @param TeamModuleManager $modManager
	 */
	public function __construct(Ab_ModuleManager $mman){
		TeamManager::$log = new TeamDebugLogger();
		$this->modManager = $mman;
		$this->moduleName = $mman->module->name;
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
	
	private $_navigatorURI;
	private $_navigatorURL;
	
	/**
	 * @return TeamNavigator
	 */
	public function Navigator($isAbs = false){
		if ($isAbs){
			if (empty($this->_navigatorURL)){
				$this->_navigatorURL = new $this->TeamNavigatorClass(true);
			}
			return $this->_navigatorURL;
		}
		if (empty($this->_navigatorURI)){
			$this->_navigatorURI = new $this->TeamNavigatorClass(false);
		}
		return $this->_navigatorURI;
	}
	
	public function IsAdminRole(){ return $this->modManager->IsAdminRole(); }
	public function IsWriteRole(){ return $this->modManager->IsWriteRole(); }
	public function IsViewRole(){ return $this->modManager->IsViewRole(); }
	
	public final function AJAX($d){
		$ret = new stdClass();
		$aDo = explode("|", $d->do);

		$aResult = array();
		for ($i=0;$i<count($aDo);$i++){
			$sDo = trim($aDo[$i]);
			if (empty($sDo)){ continue; }
			
			$d->do = $sDo;
			array_push($aResult,  $this->AJAXMethod($d));
		}
		
		$ret->result = null;
		for ($i=0;$i<count($aResult);$i++){
			$result = $aResult[$i];
			if (!is_object($result)){ continue; }
			
			if (!is_object($ret->result)){
				$ret->result = $result;
				continue;
			}
			
			$vars = get_object_vars($result);
			foreach ($vars as $var => $obj){
				$ret->result->$var = $obj;
			}
		}
		
		if ($d->initdataupdate){
			$idAX = $this->InitDataToAJAX();
			if (!empty($idAX)){
				$ret->initdata = $idAX->initdata;
			}
		}
		
		$users = TeamUserManager::ToAJAX();
		if (!empty($users)){
			$ret->users = $users;
		}
		
		if (Abricos::$config['Misc']['develop_mode']){
			TeamManager::$log->Add("SQL = ".Abricos::$db->querycount);
			$ret->log = TeamManager::$log->log;
		} 
		
		return $ret;
	}
	
	public function AJAXMethod($d){
		switch($d->do){
			case 'team':		return $this->TeamToAJAX($d->teamid);
			case 'teamsave':	return $this->TeamSave($d);
			case 'teamremove':	return $this->TeamRemove($d->teamid);
			case 'teamlist':	return $this->TeamListToAJAX($d->page, $d->memberid);
		}
		return null;
	}
	
	private $_cacheInitData = null;
	
	/**
	 * @return TeamInitData
	 */
	public function InitData(){
		if (!$this->IsViewRole()){ return null; }
		
		if (!empty($this->_cacheInitData)){
			return $this->_cacheInitData;
		}
		$this->_cacheInitData = new TeamInitData($this);
		return $this->_cacheInitData;
	}
	
	public function InitDataToAJAX(){
		$item = $this->InitData();
		if (empty($item)){ return null; }
		
		$ret = new stdClass();
		$ret->initdata = $item->ToAJAX();
		$ret->initdata->parent = $this->parentModuleName;
		
		return $ret;
	}
	
	private $_cacheTeam = array();
	
	public function TeamCacheClear($teamid = 0, $cacheName = ''){
		if ($teamid == 0){
			$this->_cacheTeam = array();
		}else{
			if (!empty($cacheName)){
				$this->_cacheTeam[$teamid][$cacheName] = null;
			}else{
				$this->_cacheTeam[$teamid] = array();
			}
		}
	}
	
	protected  function TeamCacheAdd($teamid, $cacheName, $object){
		if (!is_array($this->_cacheTeam[$teamid])){
			$this->_cacheTeam[$teamid] = array();
		}
		$this->_cacheTeam[$teamid][$cacheName] = $object;
	}
	
	public function TeamCache($teamid, $cacheName){
		if (!is_array($this->_cacheTeam[$teamid])){
			$this->_cacheTeam[$teamid] = array();
		}
		return $this->_cacheTeam[$teamid][$cacheName];
	}
	
	/**
	 * Роль пользователя в сообществе
	 * 
	 * @param integer $teamid
	 * @param integer $userid
	 * @return TeamUserRole
	 */
	public function TeamUserRole($teamid, $userid){
		$team = $this->Team($teamid);
		if (empty($team) || !$team->role->IsAdmin()){ return null; }
		
		if ($userid == $this->userid){ return $team->role; }
		
		$d = TeamQuery::TeamUserRole($this->db, $teamid, $userid);
		if (empty($d)){ return null; }
		
		return $this->NewTeamUserRole($team, $userid, $d);
	}

	/**
	 * @param integer $teamid
	 * @return Team
	 */
	public function Team($teamid, $clearCache = false){
		if (!$this->IsViewRole()){ return null; }
		
		if ($clearCache){
			$this->TeamCacheClear($teamid);
		}
		$teamid = intval($teamid);
		if (empty($teamid)){ return null; }
		
		$team = $this->TeamCache($teamid, "team");
		
		if (!empty($team)){ return $team; }

		$row = TeamQuery::Team($this, $teamid);
		if (empty($row)){ return null; }
		
		$team = $this->NewTeam($row);
		if ($this->userid > 0){
			// сделан запрос авторизованным пользователем
			// нужно отметить что он смотрел эту группу
			TeamQuery::TeamViewUser($this->db, $teamid);
		}
		
		$detail = $this->NewTeamDetail($team, $row);
		
		/*
		if ($team->role->IsAdmin()){
			$detail->inviteWaitCount = TeamQuery::MemberInviteWaitCountByTeam($this->db, $teamid);
		}
		/**/
		$team->detail = $detail;
		
		$this->TeamCacheAdd($teamid, "team", $team);
		
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
	 * Список сообществ
	 * 
	 * Если указан $userid, то список сообществ принадлежащих пользователю $userid
	 * 
	 * @param integer $page
	 * @param integer $userid
	 * 
	 * @return TeamList
	 */
	public function TeamList($page = 1, $userid = 0){
		if (!$this->IsViewRole()){ return null; }

		$rows = TeamQuery::TeamList($this, $page, $userid);
		$list = $this->NewTeamList();
		
		while (($d = $this->db->fetch_array($rows))){
			if ($d['m'] != $this->moduleName){
				$mod = Abricos::GetModule($d['m']);
				if (empty($mod)){ continue; }
				
				$modMan = $mod->GetManager();
				
				$tMan = $modMan->GetTeamManager();
				$list->Add($tMan->NewTeam($d));
			}else{
				$list->Add($this->NewTeam($d));
			}
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
	
	protected function TeamLogoSaveCheck($logo){
		if (empty($logo)){
			return '';
		}
		$fInfo = TeamQuery::FileBufferCheck($this->db, $logo);
		if (empty($fInfo)){
			return '';
		}else{
			TeamQuery::FileRemoveFromBuffer($this->db, $logo);
		}
		return $logo;
	}
	
	public function TeamSave($d){
		if (!$this->IsWriteRole()){ return null; }
	
		$teamid = $d->id = intval($d->id);
	
		$utmf = Abricos::TextParser(true);
	
		$d->tl = $utmf->Parser($d->tl);
		$d->eml = $utmf->Parser($d->eml);
		$d->site = $utmf->Parser($d->site);

		// проверка типа сообщества
		if (!empty($d->tp)){
			$d->tp = translateruen($d->tp);
			$tpMod = Abricos::GetModule($d->tp);
			if (empty($tpMod)){
				$d->tp = '';
			}
		}
		
		// $utm = Abricos::TextParser();
		// $utm->jevix->cfgSetAutoBrMode(true);
	
		$d->dsc = $utmf->Parser($d->dsc);
		
		$isNewTeam = false;
		$isModer = false;
		
		Abricos::GetModule('team')->GetManager();
		
		$cfg = TeamModuleManager::$instance->config;
	
		if ($d->id == 0){ // добавление нового общества
			$isNewTeam = true;
				
			// добавить лого можно только из своего загруженного файла 
			$d->logo = $this->TeamLogoSaveCheck($d->logo);
			
			if (!$this->IsAdminRole() && $cfg->moderationNewTeam){
				// новое сообщество необходимо рассмотреть модератору
				$isModer = true;
			}

			// TODO: необходимо реализовать ограничение на количество сообществ для участника
			$teamid = TeamQuery::TeamAppend($this->db, $this->moduleName, $this->userid, $isModer, $d);
			if ($teamid == 0){ return null; }
			
			TeamQuery::UserRoleUpdate($this->db, $teamid, $this->userid, 1, 1);
		} else {
			
			$team = $this->Team($teamid);
			
			if (empty($team) || !$team->role->IsAdmin()){ return null; }
				
			if ($team->logo != $d->logo){
				if (!empty($team->logo)){
					// добавить текущий файл в буффер для последующей зачистки
					TeamQuery::FileAddToBuffer($this->db, $this->userid, $team->logo, '');
				}
				
				$d->logo = $this->TeamLogoSaveCheck($d->logo);
			}
			
			TeamQuery::TeamUpdate($this->db, $d);
		}
		
		$this->TeamMemberCountRecalc($teamid);
		
		Abricos::GetModule('team');
		
		TeamModule::$instance->GetManager()->FileBufferClear();
		
		$this->TeamCacheClear();
		
		if ($isNewTeam){
			// выполнить событие (отправка уведомлений и т.п.)
			$this->OnTeamAppend($teamid, $isModer);
		}
	
		return $teamid;
	}
	
	/**
	 * Событие на создание нового сообщества
	 * Используется для отправки необходимых уведомлений
	 * 
	 * Если $isModer=true, новое сообщество требует модерацию
	 * 
	 * @param integer $teamid
	 * @param integer $isModer 
	 */
	protected function OnTeamAppend($teamid, $isModer){ }
	
	public function TeamRemove($teamid){
		$team = $this->Team($teamid);
		if (is_null($team) || !$team->role->IsAdmin()){
			return null;
		}
		TeamQuery::TeamRemove($this->db, $teamid);
		$this->TeamCacheClear($teamid);
		return true;
	}
	
	/**
	 * Пересчитать количество участников в сообществе
	 * @param integer $teamid
	 * @return integer количество участников
	 */
	public function TeamMemberCountRecalc($teamid){
		$cnt = TeamQuery::TeamMemberCountRecalc($this->db, $teamid);
		return $cnt;
	}
		
}

?>