<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class TeamModule
 */
class TeamModule extends Ab_Module {

    public function __construct(){
        $this->version = "0.1.0";
        $this->name = "team";
        $this->takelink = "team";
        $this->permission = new TeamPermission($this);
    }

    public function GetContentName(){
        $adress = Abricos::$adress;
        $lvl = $adress->level;
        $dir = $adress->dir;

        if ($lvl >= 2 && $dir[1] == 'uploadlogo'){
            return 'uploadlogo';
        }

        return "teamapp";
    }
}


class TeamAction {
    const VIEW = 10;
    const WRITE = 20;
    const TEAM_WRITE = 30;
    const MEMBER_WRITE = 40;
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

            new Ab_UserRole(TeamAction::TEAM_WRITE, Ab_UserGroup::REGISTERED),
            new Ab_UserRole(TeamAction::TEAM_WRITE, Ab_UserGroup::ADMIN),

            new Ab_UserRole(TeamAction::MEMBER_WRITE, Ab_UserGroup::REGISTERED),
            new Ab_UserRole(TeamAction::MEMBER_WRITE, Ab_UserGroup::ADMIN),

            new Ab_UserRole(TeamAction::ADMIN, Ab_UserGroup::ADMIN),
        );
        parent::__construct($module, $defRoles);
    }

    public function GetRoles(){
        return array(
            TeamAction::VIEW => $this->CheckAction(TeamAction::VIEW),
            TeamAction::WRITE => $this->CheckAction(TeamAction::WRITE),
            TeamAction::TEAM_WRITE => $this->CheckAction(TeamAction::TEAM_WRITE),
            TeamAction::MEMBER_WRITE => $this->CheckAction(TeamAction::MEMBER_WRITE),
            TeamAction::ADMIN => $this->CheckAction(TeamAction::ADMIN)
        );
    }
}

Abricos::ModuleRegister(new TeamModule());
