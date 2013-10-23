<?php 
/**
 * @package Abricos
 * @subpackage Team
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Модуль управления группой людей (сообщество, компания, товарищество и т.п.)
 * 
 * Базовые понятия:
 * 	Team - команда, группа, сообщество, компания и т.п.
 * 	Member - участник группы, может быть в следующих статусах:
 * 		isMember - участник группы,
 * 		isJoinRequest - запрос на вступление в группу исходит от участника,
 * 		isInvite - приглашение в группу от админа группы
 * 
 */
class TeamModule extends Ab_Module {

	/**
	 * @var TeamModule
	 */
	public static $instance;
	
	public function __construct(){
		TeamModule::$instance = $this;
		
		$this->version = "0.1.2";
		$this->name = "team";
		$this->takelink = "team";
		$this->permission = new TeamPermission($this);
	}
	
	/**
	 * Получить менеджер
	 *
	 * @return TeamModuleManager
	 */
	public function GetManager(){
		if (is_null($this->_manager)){
			require_once 'includes/manager.php';
			$this->_manager = new TeamModuleManager($this);
		}
		return $this->_manager;
	}
	
	/**
	 * @var Team
	 */
	public $currentTeam;
	
	public $currentMemberId;
	
	/**
	 * @var Ab_Module
	 */
	public $currentMemberMod;
	
	public function GetContentName(){
		$cname = '';
		$adress = $this->registry->adress;
		$lvl = $adress->level;
		$dir = $adress->dir;
	
		if ($lvl >= 2 && $dir[1] == 'uploadlogo'){
			return 'uploadlogo';
		}
		
		$p = $dir[1];
		if (!empty($p) && substr($p, 0, 1) == 't'){
			$teamid = bkint(substr($p, 1));
			
			$team = $this->GetManager()->Team($teamid);
			if (empty($team)){ return ''; }
			
			$cname = 'teamview';
			
			$this->currentTeam = $team;
			
			if ($dir[2] == 'member'){
				
				if ($dir[3] == 'by' && !empty($dir[4])){
					$memberMod = Abricos::GetModule($dir[4]);
					if (empty($memberMod)){ return ''; }
					
					$this->currentMemberMod = $memberMod;

					$p = $dir[5];
					if (!empty($p) && substr($p, 0, 1) == 'm'){
						$memberid = bkint(substr($p, 1));
						$this->currentMemberId = $memberid;
						
						return 'memberview';
					}
				}
				return '';
			}
		}else{
			return 'teamlist';
		}
		
		return $cname;
	}
}


class TeamAction {
	
	const VIEW	= 10;
	
	const WRITE	= 30;
	
	const ADMIN	= 50;
}

class TeamPermission extends Ab_UserPermission {
	
	public function TeamPermission(TeamModule $module){
		
		$defRoles = array(
			new Ab_UserRole(TeamAction::VIEW, Ab_UserGroup::GUEST),
			new Ab_UserRole(TeamAction::VIEW, Ab_UserGroup::REGISTERED),
			new Ab_UserRole(TeamAction::VIEW, Ab_UserGroup::ADMIN),
			
			new Ab_UserRole(TeamAction::WRITE, Ab_UserGroup::REGISTERED),
			new Ab_UserRole(TeamAction::WRITE, Ab_UserGroup::ADMIN),
			
			new Ab_UserRole(TeamAction::ADMIN, Ab_UserGroup::ADMIN),
		);
		parent::__construct($module, $defRoles);
	}
	
	public function GetRoles(){
		return array(
			TeamAction::VIEW => $this->CheckAction(TeamAction::VIEW),
			TeamAction::WRITE => $this->CheckAction(TeamAction::WRITE),
			TeamAction::ADMIN => $this->CheckAction(TeamAction::ADMIN)
		);
	}
}

Abricos::ModuleRegister(new TeamModule());

?>