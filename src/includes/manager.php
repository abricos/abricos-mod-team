<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class TeamManager
 */
class TeamManager extends Ab_ModuleManager {

    public function IsAdminRole(){
        return $this->IsRoleEnable(TeamModuleAction::ADMIN);
    }

    public function IsTeamAppendRole(){
        return $this->IsRoleEnable(TeamModuleAction::TEAM_APPEND);
    }

    public function IsWriteRole(){
        return $this->IsRoleEnable(TeamModuleAction::WRITE);
    }

    public function IsViewRole(){
        return $this->IsRoleEnable(TeamModuleAction::VIEW);
    }

    public function AJAX($d){
        return $this->GetApp()->AJAX($d);
    }

    public function Bos_MenuData(){
        if (!$this->IsViewRole()){
            return null;
        }
        $i18n = $this->module->I18n();
        return array(
            array(
                "name" => "team",
                "title" => $i18n->Translate('title'),
                "icon" => "/modules/team/images/icon.gif",
                "url" => "team/wspace/ws",
            )
        );
    }
}
