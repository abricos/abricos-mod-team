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
	
	public $MemberClass			= Member;
	public $MemberDetailClass	= MemberDetail;
	public $MemberListClass		= MemberList;
	
	public $MemberGroupClass	= MemberGroup;
	public $MemberGroupListClass= MemberGroupList;
	
	public $TeamNavigatorClass	= TeamNavigator;
	
	public $TeamUserConfigClass	= TeamUserConfig;
		
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
	
	private $_navigatorURI;
	private $_navigatorURL;
	
	/**
	 * @return TeamNavigator
	 */
	public function Navigator($isURL = false){
		if ($isURL){
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
	
	/**
	 * @param Team $team
	 * @param array $d
	 * @return MemberGroup
	 */
	// public function NewMemberGroup(Team $team, $d){ return new $this->MemberGroupClass($team, $d); }
	
	/**
	 * @return MemberGroupList
	 */
	// public function NewMemberGroupList(){ return new $this->MemberGroupListClass(); }
	
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
		
		if ($d->globalmemberlist && $d->teamid > 0){
			$team = $this->Team($d->teamid);
			if ($team->module == $this->moduleName && $d->do == 'memberlist'){
				// TODO: убрать дубликат данных
			}
			$mod = Abricos::GetModule($team->module);
			if (!empty($mod)){
				$man = $mod->GetManager()->GetTeamManager();
				$obj = $man->MemberListToAJAX($team->id);
				if (!empty($obj)){
					$ret->globalmemberlist = $obj->members;
				}
			}
		}
		
		$users = TeamUserManager::ToAJAX();
		if (!empty($users)){
			$ret->users = $users;
		}
		
		if ($d->userconfigupdate){
			$ret->userconfig = $this->UserConfigToAJAX();
		}
		
		if ($d->initdataupdate){
			$idAX = $this->InitDataToAJAX();
			if (!empty($idAX)){
				$ret->initdata = $idAX->initdata;
			}
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
			
			case 'member':	 	return $this->MemberToAJAX($d->teamid, $d->memberid);
			case 'memberlist': 	return $this->MemberListToAJAX($d->teamid);
			case 'membersave': 	return $this->MemberSaveToAJAX($d->teamid, $d->savedata);
			case 'memberremove':return $this->MemberRemove($d->teamid, $d->memberid);
			
			case 'membergrouplist':	return $this->MemberGroupListToAJAX($d->teamid);
			case 'membergroupsave': return $this->MemberGroupSaveToAJAX($d->teamid, $d->savedata);
			case 'membergroupremove': return $this->MemberGroupRemoveToAJAX($d->teamid, $d->groupid);

			case 'memberinviteact': return $this->MemberInviteAcceptToAJAX($d->teamid, $d->userid, $d->flag);
			
			case 'mynamesave': return $this->MyNameSave($d);
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
		
// TeamManager::$log->Add("Team Get $this->moduleName = $teamid");
		
		$team = $this->TeamCache($teamid, "team");
		
		if (!empty($team)){ return $team; }

// TeamManager::$log->Add("Team Load $this->moduleName = $teamid");
		
		$row = TeamQuery::Team($this, $teamid);
		if (empty($row)){ return null; }
		
		$team = $this->NewTeam($row);
		if ($team->module != $this->moduleName){
			// сообщество перегружено еще одним модулем
			// необходимо проверить доступ к этому сообществу
			$mod = Abricos::GetModule($team->module);
			if (empty($mod)){ return null; }
			if (!$mod->GetManager()->IsViewRole()) { return null; }
		}

		if ($this->userid > 0){
			// сделан запрос авторизованным пользователем
			// нужно отметить что он смотрел эту группу
			TeamQuery::UserTeamView($this->db, $teamid);
		}
		
		$detail = $this->NewTeamDetail($team, $row);
		
		if ($team->role->IsAdmin()){
			$detail->inviteWaitCount = TeamQuery::MemberInviteWaitCountByTeam($this->db, $teamid);
		}
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
	 * Если указан $memberid, то список сообществ принадлежащих пользователю $memberid
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
		
		Abricos::GetModule('team');
		TeamModule::$instance->GetManager();
		
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
	
	/**
	 * Роль участника в сообществе 
	 * 
	 * @param integer $teamid
	 * @param integer $memberid
	 * @return TeamUserRole
	 */
	public function TeamUserRole($teamid, $memberid){
		$team = $this->Team($teamid);
		if (empty($team)){ return null; }
		
		$cacheName = "teamuserrole".$memberid;
		
		$role = $this->TeamCache($teamid, $cacheName);
		if (!empty($role)){ return $role; }
		
		$row = TeamQuery::Member($this, $team, $memberid);
		if (empty($row)){
			$this->TeamCacheClear($teamid, $cacheName);
			return null; 
		}
		$role = $this->NewTeamUserRole($team, $memberid, $row);
		$this->TeamCacheAdd($teamid, $cacheName, $role);
		
		return $role; 
	}
		
	/**
	 * Участник сообщества
	 * 
	 * @param integer $teamid
	 * @param integer $memberid
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
	public function MemberList($teamid, $clearCache = false){
		$team = $this->Team($teamid);

		if (empty($team)){ return null; }
		
		$cacheName = "memberlist";
		
		if ($clearCache){
			$this->TeamCacheClear($teamid, $cacheName);
		}
		
		$list = $this->TeamCache($teamid, $cacheName);
		
		if (!empty($list)){ return $list; }
		
		$rows = TeamQuery::MemberList($this, $team);
		$list = $this->NewMemberList();
		while (($d = $this->db->fetch_array($rows))){
			$member = $this->NewMember($team, $d);
			$list->Add($this->NewMember($team, $d));
			
			TeamUserManager::AddId($member->id);
		}
		$this->TeamCacheAdd($teamid, $cacheName, $list);

		return $list;
	}
	
	public function MemberListToAJAX($teamid, $clearCache = false){
		$list = $this->MemberList($teamid, $clearCache);
		if (empty($list)){ return null; }
		
		$ret = new stdClass();
		$ret->members = $list->ToAJAX();
		
		$obj = $this->MemberGroupListToAJAX($teamid);
		$ret->membergroups = $obj->membergroups;

		$obj = $this->MemberInGroupListToAJAX($teamid);
		$ret->memberingroups = $obj->memberingroups;
		
		return $ret;
	}
	
	public function MemberSave($teamid, $d){
		$team = $this->Team($teamid);
		
		if (!$team->role->IsAdmin()){ // текущий пользователь не админ => нет прав
			return null;
		}
		$d->id = intval($d->id);
		
		if ($d->id == 0){ // Добавление участника
			
			if ($d->vrt == 1){ // Добавление виртуального участника
				
				$invite = $this->MemberNewInvite($team, $d->email, $d->fnm, $d->lnm, true);
				
				if (is_null($invite)){
					return null;
				}
				$d->id = $invite->user['id'];
				
			}else{
				
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
						$invite = $this->MemberNewInvite($team, $d->email, $d->fnm, $d->lnm, false);
						if (is_null($invite)){
							return null;
						}
						
						// отправка уведомление
						$this->MemberNewInviteSendMail($team, $d->email, $d->fnm, $d->lnm, $invite);
						
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
						
						// отправка уведомления
						$this->MemberInviteSendMail($team, $d->id);
					}
				}				
			}

		}else{
			
		}
		
		$this->TeamCacheClear($teamid);
		
		$memberid = $d->id;
		
		// сохранение группы пользователя
		$groupid = intval($d->groupid);
		
		$mgList = $this->MemberGroupList($teamid);
		$mg = $mgList->Get($groupid);
		if (empty($mg)){
			$groupid = 0;
		}
		
		$migList = $this->MemberInGroupList($teamid);
		$mig = $migList->GetByMemberId($memberid);
		$curgroupid = empty($mig) ? 0 : $mig->groupid;
		
		if ($groupid != $curgroupid){ // изменения по группе
			
			// удалить из текущей
			TeamQuery::MemberRemoveFromGroup($this->db, $curgroupid, $memberid);
			
			// добавить в новую
			if ($groupid > 0){
				TeamQuery::MemberAddToGroup($this->db, $groupid, $memberid);
			}
		}
		
		$this->TeamMemberCountRecalc($teamid);
		
		return $d->id;		
	}
	
	public function MemberSaveToAJAX($teamid, $d){
		$memberid = $this->MemberSave($teamid, $d);
		if (empty($memberid)){ return null; }
		
		$this->TeamCacheClear($teamid);
		
		$ret = $this->MemberListToAJAX($teamid);
		
		$obj = $this->MemberToAJAX($teamid, $memberid);
		$ret->memberid = $memberid;
		$ret->member = $obj->member;
		
		return $ret;
	}
	
	/**
	 * Зарегистрировать нового пользователя
	 * 
	 * Если пользователь виртуальный, то его можно будет пригласить позже.
	 * Виртаульный пользователь необходим для того, чтобы можно было работать с 
	 * его учеткой как с реальным пользователем. Допустим, создается список сотрудников
	 * компании. Выяснять их существующие емайлы или регить новые - процесс длительный,
	 * а работать в системе уже нужно сейчас. Поэтому сначало создается виртуальный
	 * пользователь, а уже потом, если будет он переводиться в статус реального
	 * с формированием пароля и отправкой приглашения.
	 *
	 * @param string $email
	 * @param string $fname Имя
	 * @param string $lname Фамилия
	 * @param boolean $isVirtual True-виртуальный пользователь
	 */
	protected function MemberNewInvite(Team $team, $email, $fname, $lname, $isVirtual = false){
	
		Abricos::GetModule('invite');
		$manInv = InviteModule::$instance->GetManager();
	
		// зарегистрировать пользователя (будет сгенерировано имя и пароль)
		$invite = $manInv->UserRegister($this->moduleName, $email, $fname, $lname, $isVirtual);
	
		if ($invite->error == 0){
			if ($isVirtual){
				// виртуальному пользователю сразу ставим статус подвержденного свою учетку
				TeamQuery::UserSetMember($this->db, $team->id, $invite->user['id']);
			}else{
				// пометка пользователя флагом приглашенного
				// (система ожидает подтверждение от пользователя)
				TeamQuery::MemberInviteSetWait($this->db, $team->id, $invite->user['id'], $this->userid);
			}
		}
	
		return $invite;
	}

	/**
	 * Отправка приглашения новому участнику
	 * 
	 * @param Team $team
	 * @param unknown_type $email
	 * @param unknown_type $fname
	 * @param unknown_type $lname
	 * @param unknown_type $invite
	 */
	protected function MemberNewInviteSendMail(Team $team, $email, $fname, $lname, $invite){
		$inu = $invite->user;
		
		$repd = array(
			"author" => TeamUserManager::Get($this->userid)->UserNameBuild(),
			"teamtitle" => $team->title,
			"username" => $fname." ".$lname,
			"inviteurl" =>  $invite->URL."/".$this->Navigator()->MemberView($team->id, $inu['id'], $this->moduleName),
			"login" => $inu['login'],
			"password" => $inu['password'],
			"email" => $email,
			"teamurl" => $this->Navigator(true)->TeamView($team->id),
			"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
		);
		
		$brick = Brick::$builder->LoadBrickS($this->moduleName, 'templates', null, null);
		if (empty($brick)
				|| empty($brick->param->var['mbrinvitesubject'])
				|| empty($brick->param->var['mbrinvitebody'])){
			
			$brick = Brick::$builder->LoadBrickS("team", 'templates', null, null);
		}
		$v = &$brick->param->var;
		
		$subject = Brick::ReplaceVarByData($v['mbrinvitesubject'], $repd);
		$body = Brick::ReplaceVarByData($v['mbrinvitebody'], $repd);
		
		Abricos::Notify()->SendMail($email, $subject, $body);
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
	
	/**
	 * Отправить приглашение на вступление существующему пользователю
	 * 
	 * @param Team $team
	 * @param integer $userid
	 */
	protected function MemberInviteSendMail(Team $team, $userid){
		
		$userEml = UserQueryExt::User($this->db, $userid);
		
		$host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
		
		$repd = array(
			"author" => TeamUserManager::Get($this->userid)->UserNameBuild(),
			"teamtitle" => $team->title,
			"username" => TeamUserManager::Get($userid)->UserNameBuild(),
			"inviteurl" => $this->Navigator(true)->MemberView($team->id, $userid, $this->moduleName),
			"email" => $userEml['email'],
			"teamurl" => $this->Navigator(true)->TeamView($team->id),
			"sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
		);
		
		$brick = Brick::$builder->LoadBrickS($this->moduleName, 'templates', null, null);
		if (empty($brick)
				|| empty($brick->param->var['mbraddsubject'])
				|| empty($brick->param->var['mbraddbody'])){
				
			$brick = Brick::$builder->LoadBrickS("team", 'templates', null, null);
		}
		$v = &$brick->param->var;
		
		$subject = Brick::ReplaceVarByData($v['mbraddsubject'], $repd);
		$body = Brick::ReplaceVarByData($v['mbraddbody'], $repd);
		
		Abricos::Notify()->SendMail($d->email, $subject, $body);
	}
	
	/**
	 * Принять/отказать приглашение пользователя в сообещство
	 * 
	 * @param integer $teamid
	 * @param integer $memberid
	 * @param boolean $flag TRUE-принять, FALSE-отказать
	 */
	public function MemberInviteAccept($teamid, $memberid, $flag){
		$member = $this->Member($teamid, $memberid);
		
		if (empty($member) || $member->id != $this->userid){ return null; }
		
		if ($flag){
			TeamQuery::MemberInviteSetAccept($this->db, $teamid, $memberid);
		}else{
			TeamQuery::MemberInviteSetReject($this->db, $teamid, $memberid);
		}
		
		$this->TeamMemberCountRecalc($teamid);
		
		$this->TeamCacheClear($teamid);
		
		return $memberid;
	}
	
	public function MemberInviteAcceptToAJAX($teamid, $memberid, $flag){
		$memberid = $this->MemberInviteAccept($teamid, $memberid, $flag);

		return $this->MemberToAJAX($teamid, $memberid);
	}
	
	public function MemberRemove($teamid, $memberid){
		$team = $this->Team($teamid);
		if (empty($team) || !$team->role->IsAdmin()){ return null; }
		
		TeamQuery::MemberRemove($this->db, $teamid, $memberid);
		
		$this->TeamMemberCountRecalc($teamid);
		
		return true;
	}
	
	/**
	 * Список групп участников
	 * @param integer $teamid
	 * @return MemberGroup
	 */
	public function MemberGroupList($teamid, $clearCache = false){
		$team = $this->Team($teamid);
		if (empty($team)){ return null; }

		$cacheName = "mglist";
		
		if ($clearCache){
			$this->TeamCacheClear($teamid, $cacheName);
		}
		$list = $this->TeamCache($teamid, $cacheName);
	
		if (!empty($list)){ return $list; }
			
		$list = new MemberGroupList();
		$rows = TeamQuery::MemberGroupList($this->db, $teamid, $this->moduleName);
		while (($d = $this->db->fetch_array($rows))){
			$list->Add(new MemberGroup($d));
		}
		
		$this->TeamCacheAdd($teamid, $cacheName, $list);
		
		return $list;
	}
	
	public function MemberGroupListToAJAX($teamid){
		$list = $this->MemberGroupList($teamid);
		if (empty($list)){ return null; }
		
		$ret = new stdClass();
		$ret->membergroups = $list->ToAJAX();
		
		return $ret;
	}
	
	public function MemberGroupSave($teamid, $d){
		$team = $this->Team($teamid);
		if (empty($team) || !$team->role->IsAdmin()){
			return null;
		}
	
		$utmf = Abricos::TextParser(true);
		$d->tl = $utmf->Parser($d->tl);
	
		if (empty($d->tl)){ return null; }
	
		if ($d->id == 0){
			$d->id = TeamQuery::MemberGroupAppend($this->db, $teamid, $this->moduleName, $d);
		}else{
			TeamQuery::MemberGroupUpdate($this->db, $teamid, $this->moduleName, $d->id, $d);
		}
		
		$this->TeamCacheClear($teamid);
		
		return $d->id;
	}
	
	public function MemberGroupSaveToAJAX($teamid, $d){
		$groupid = $this->MemberGroupSave($teamid, $d);
		if (empty($groupid)){ return null; }
		
		$ret = $this->MemberGroupListToAJAX($teamid);
		$ret->groupid = $groupid;
		
		return $ret;
	}
	
	public function MemberInGroupList($teamid, $clearCache = false){
		$team = $this->Team($teamid);
		if (empty($team)){ return null; }
		
		$cacheName = "miglist";
		
		if ($clearCache){
			$this->TeamCacheClear($teamid, $cacheName);
		}
		
		$list = $this->TeamCache($teamid, $cacheName);
		
		if (!empty($list)){ return $list; }
		
		$list = new MemberInGroupList();
		$rows = TeamQuery::MemberInGroupList($this->db, $teamid, $this->moduleName);
		while (($d = $this->db->fetch_array($rows))){
			$list->Add(new MemberInGroup($d));
		}
		$this->TeamCacheAdd($teamid, $cacheName, $list);
		return $list;
	}
	
	public function MemberInGroupListToAJAX($teamid){
		$list = $this->MemberInGroupList($teamid);
		if (empty($list)){ return null; }
	
		$ret = new stdClass();
		$ret->memberingroups = $list->ToAJAX();
	
		return $ret;
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