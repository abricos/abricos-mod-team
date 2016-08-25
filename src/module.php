<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Модуль управления группой людей (сообщество, компания, товарищество и т.п.)
 *
 * Базовые понятия:
 *    Team - команда, группа, сообщество, компания и т.п.
 *    Member - участник группы, может быть в следующих статусах:
 *        isMember - участник группы,
 *        isJoinRequest - запрос на вступление в группу исходит от участника,
 *        isInvite - приглашение в группу от админа группы
 *
 */
class TeamModule extends Ab_Module {

    public function __construct(){
        $this->version = "0.2.0";
        $this->name = "team";
        $this->takelink = "team";
        $this->permission = new TeamPermission($this);
    }

    /**
     * @var Team
     */
    public $currentTeam = null;

    /**
     * @var TeamAppManager
     */
    public $currentTeamApp = null;

    /**
     * Определение стартового кирпича
     *
     * Парсит URL
     *
     * /team/ - список типов сообществ
     * /team/[modname]/ - список сообществ определенного типа (модуля)
     * /team/[teamid]/ - сообщество
     * /team/[teamid]/[modname]/[appname]/ - приложение сообщества
     */
    public function GetContentName(){
        $adress = Abricos::$adress;
        $lvl = $adress->level;
        $dir = $adress->dir;

        if ($lvl == 1){
            return 'teammodlist';
        }

        if ($lvl >= 2 && $dir[1] == 'uploadlogo'){
            return 'uploadlogo';
        }

        $teamid = intval($dir[1]);
        $team = null;
        if ($teamid > 0 && $teamid == $dir[1]){
            $team = $this->GetManager()->Team($teamid);
            if (empty($team)){
                return '';
            }
        }

        $this->currentTeam = $team;

        if ($lvl == 2){
            return 'teamview';
        }

        $modName = $dir[2];
        $appName = $dir[3];

        $appManager = $this->GetManager()->GetTeamAppManager($modName, $appName);
        if (empty($appManager)){
            return '';
        }

        $this->currentTeamApp = $appManager;

        return "teamapp";
    }
}


class TeamAction {
    const VIEW = 10;

    const WRITE = 30;

    const ADMIN = 50;
}

class TeamPermission extends Ab_UserPermission {

    public function __construct(TeamModule $module){
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
