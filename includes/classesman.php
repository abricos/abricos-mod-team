<?php 
/**
 * @package Abricos
 * @subpackage Team
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

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
	public function __construct(Ab_ModuleManager $mman){
		$this->modManager = $mman;
		$this->modname = $mman->module->name;
		$this->db = $mman->db;
		$this->user = $mman->user;
		$this->userid = $mman->userid;
	}
	
	/**
	 * @param Member $member
	 * @param array $d
	 * @return Team
	 */
	public abstract function NewTeam($d);
	
	public abstract function NewTeamUserRole(Team $team, $userid, $d);
	
	/**
	 * @return TeamList
	 */
	public function NewTeamList(){ return new $this->TeamListClass(); }
	
	/**
	 * @param array $d
	 * @return Member
	 */
	public function NewMember($d){ return new $this->MemberClass($d); }
	
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
			
			case 'mynamesave': return $this->MyNameSave($d);
		}
		return null;
	}
	
	protected function TeamDetail(){
		
	}
	
	/**
	 * @param integer $teamid
	 * @return Team
	 */
	public function Team($teamid){
		if (!$this->IsViewRole()){ return null; }

		$row = TeamQuery::Team($this->db, $this->modname, $teamid);
		if (empty($row)){ return null; }
		
		$team = $this->NewTeam($member, $row);
		$detail = new TeamDetail();
		
		if ($member->IsAdmin()){
			$detail->inviteWaitCount = TeamQuery::MemberInviteWaitCountByTeam($this->db, $teamid);
		}
		$team->detail = $detail;
		
		if ($this->userid > 0){
			// сделан запрос авторизованным пользователем
			// нужно отметить что он смотрел эту группу
			TeamQuery::UserTeamView($this->db, $teamid);
		}
		
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
		$list = new $this->NewTeamList();
		
		while (($row = $man->db->fetch_array($rows))){
			$list->Add();
			array_push($list, new $man->TeamClass($man, $row));
		}
		$this->list = $list;
		
		
		return $list;
	}
	
	public function TeamListToAJAX($page = 1, $limit = 15){
		$teamList = $this->TeamList($page, $limit);
		
		if (is_null($teamList)){ return null; }
		
		return $teamList->ToAJAX();
	}
	
	public function TeamSave($d){
		if (!$this->IsWriteRole()){
			return null;
		}
	
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