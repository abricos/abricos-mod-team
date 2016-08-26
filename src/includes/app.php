<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class TeamApp
 *
 * @property TeamManager $manager
 */
class TeamApp extends AbricosApplication {

    protected function GetClasses(){
        return array(
            "Team" => "Team",
            "TeamList" => "TeamList",
            "UserRole" => "TeamUserRole",
            "UserRoleList" => "TeamUserRoleList",
        );
    }

    protected function GetStructures(){
        return 'Team,UserRole';
    }

    public function ResponseToJSON($d){
        switch ($d->do){

        }
        return null;
    }

    public function IsAdminRole(){
        return $this->manager->IsAdminRole();
    }

    public function IsWriteRole(){
        return $this->manager->IsWriteRole();
    }

    public function IsViewRole(){
        return $this->manager->IsViewRole();
    }

}
