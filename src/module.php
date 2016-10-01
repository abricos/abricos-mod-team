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

    public function Bos_IsMenu(){
        return true;
    }
}


class TeamModuleAction {
    const VIEW = 10;
    const WRITE = 20;
    const TEAM_APPEND = 30;
    const ADMIN = 50;
}

class TeamPermission extends Ab_UserPermission {

    public function __construct(TeamModule $module){
        $defRoles = array(
            new Ab_UserRole(TeamModuleAction::VIEW, Ab_UserGroup::GUEST),
            new Ab_UserRole(TeamModuleAction::VIEW, Ab_UserGroup::REGISTERED),
            new Ab_UserRole(TeamModuleAction::VIEW, Ab_UserGroup::ADMIN),

            new Ab_UserRole(TeamModuleAction::WRITE, Ab_UserGroup::REGISTERED),
            new Ab_UserRole(TeamModuleAction::WRITE, Ab_UserGroup::ADMIN),

            new Ab_UserRole(TeamModuleAction::TEAM_APPEND, Ab_UserGroup::REGISTERED),
            new Ab_UserRole(TeamModuleAction::TEAM_APPEND, Ab_UserGroup::ADMIN),

            new Ab_UserRole(TeamModuleAction::ADMIN, Ab_UserGroup::ADMIN),
        );
        parent::__construct($module, $defRoles);
    }

    public function GetRoles(){
        return array(
            TeamModuleAction::VIEW => $this->CheckAction(TeamModuleAction::VIEW),
            TeamModuleAction::WRITE => $this->CheckAction(TeamModuleAction::WRITE),
            TeamModuleAction::TEAM_APPEND => $this->CheckAction(TeamModuleAction::TEAM_APPEND),
            TeamModuleAction::ADMIN => $this->CheckAction(TeamModuleAction::ADMIN)
        );
    }
}

Abricos::ModuleRegister(new TeamModule());
