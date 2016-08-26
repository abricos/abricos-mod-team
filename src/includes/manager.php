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
        return $this->IsRoleEnable(TeamAction::ADMIN);
    }

    public function IsWriteRole(){
        return $this->IsRoleEnable(TeamAction::WRITE);
    }

    public function IsViewRole(){
        return $this->IsRoleEnable(TeamAction::VIEW);
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
                "group" => "personal",
                "title" => $i18n->Translate('title'),
                "icon" => "/modules/team/images/icon.png",
                "url" => "team/wspace/ws"
            )
        );
    }
}
