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
            case 'teamList':
                return $this->TeamListToJSON($d->filter);
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

    public function TeamSave($ownerModule, $d){
        if (!$this->IsWriteRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $utmf = Abricos::TextParser(true);
        $d->id = intval($d->id);
        $d->title = $utmf->Parser($d->title);

        if ($d->id === 0){
            $d->id = TeamQuery::TeamAppend($this->db, $ownerModule, $d);
        } else {
        }

        $ret = new stdClass();
        $ret->teamid = $d->id;
        return $ret;
    }

    public function TeamListToJSON($filter){
        $res = $this->TeamList($filter);
        return $this->ResultToJSON('teamList', $res);
    }

    public function TeamList($filter){
        if (!$this->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        if (!is_object($filter)){
            $filter = new stdClass();
        }
        $filter->ownerModule = isset($filter->ownerModule) ? $filter->ownerModule : '';


        /** @var TeamList $list */
        $list = $this->InstanceClass('TeamList');
        $rows = TeamQuery::TeamList($this->db, $filter->ownerModule);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->InstanceClass('Team', $d));
        }
        return $list;
    }
}
