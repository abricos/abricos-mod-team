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
            "Member" => "TeamMember",
            "MemberList" => "TeamMemberList",
        );
    }

    protected function GetStructures(){
        return 'Team,Member';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case "teamSave":
                return $this->TeamSaveToJSON($d->data);
            case 'teamList':
                return $this->TeamListToJSON($d->filter);
            case 'team':
                return $this->TeamToJSON($d->teamid);
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

    private function OwnerAppFunctionExist($module, $fn){
        $ownerApp = Abricos::GetApp($module);
        if (empty($ownerApp)){
            return false;
        }
        if (!method_exists($ownerApp, $fn)){
            return false;
        }
        return true;
    }

    public function TeamSaveToJSON($d){
        $res = $this->TeamSave($d);
        return $this->ResultToJSON('teamSave', $res);
    }

    public function IsTeamAppend($ownerModule){
        if (!$this->OwnerAppFunctionExist($ownerModule, 'Team_IsAppend')){
            return AbricosResponse::ERR_SERVER_ERROR;
        }
        $ownerApp = Abricos::GetApp($ownerModule);
        if (!$ownerApp->Team_IsAppend()){
            return AbricosResponse::ERR_FORBIDDEN;
        }
        return 0;
    }

    public function OnTeamSave(Team $team, $d){
        if (!$this->OwnerAppFunctionExist($team->module, 'Team_OnTeamSave')){
            return;
        }
        $ownerApp = Abricos::GetApp($team->module);
        $ownerApp->Team_OnTeamSave($team, $d);
    }

    public function TeamSave($d){
        if (!$this->IsWriteRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $utmf = Abricos::TextParser(true);

        /** @var Team $team */
        $team = $this->InstanceClass('Team', $d);
        $team->title = $utmf->Parser($team->title);

        if ($team->id === 0){
            if (($err = $this->IsTeamAppend($team->module)) > 0){
                return $err;
            }

            $team->id = TeamQuery::TeamAppend($this->db, $team->module, $team);

            if (empty($team->id)){
                return AbricosResponse::ERR_SERVER_ERROR;
            }

            $this->MemberSaveMethod($team, array(
                "teamid" => $team->id,
                "userid" => Abricos::$user->id,
                "relUserId" => Abricos::$user->id,
                "isMember" => true,
                "isAdmin" => true,
            ));
        } else {
        }

        $this->OnTeamSave($team, $d);

        $this->CacheClear();

        $ret = new stdClass();
        $ret->teamid = $team->id;
        return $ret;
    }

    public function TeamToJSON($teamid){
        $res = $this->Team($teamid);
        if (AbricosResponse::IsError($res)){
            return $res;
        }

        return $this->ResultToJSON('team', $res);
    }

    public function OnTeam(Team $team){
        if (!$this->OwnerAppFunctionExist($team->module, 'Team_OnTeam')){
            return;
        }
        $ownerApp = Abricos::GetApp($team->module);
        $ownerApp->Team_OnTeam($team);
    }

    /**
     * @param int $teamid
     * @return int|Team
     */
    public function Team($teamid){
        $teamid = intval($teamid);
        if (!isset($this->_cache['Team'])){
            $this->_cache['Team'] = array();
        }
        if (isset($this->_cache['Team'][$teamid])){
            return $this->_cache['Team'][$teamid];
        }
        if (!$this->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $d = TeamQuery::Team($this->db, $teamid);
        if (empty($d)){
            return AbricosResponse::ERR_NOT_FOUND;
        }

        /** @var Team $team */
        $team = $this->InstanceClass('Team', $d);

        $team->members = $this->MemberListMethod($team);

        $this->_cache['Team'][$teamid] = $team;

        $this->OnTeam($team);

        return $team;
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

    public function OnMemberSave(Team $team, TeamMember $member, $d){
        if (!$this->OwnerAppFunctionExist($team->module, 'Team_OnMemberSave')){
            return;
        }
        $ownerApp = Abricos::GetApp($team->module);
        $ownerApp->Team_OnMemberSave($team, $member, $d);
    }

    public function MemberSaveMethod(Team $team, $d){
        /** @var TeamMember $member */
        $member = $this->InstanceClass('Member', $d);

        if ($member->id === 0){
            $member->id = TeamQuery::MemberAppend($this->db, $member);
        } else {
        }
        $this->OnMemberSave($team, $member, $d);
    }

    public function MemberSave($teamid, $d){

    }

    public function OnMemberList(Team $team, TeamMemberList $list){
        if (!$this->OwnerAppFunctionExist($team->module, 'Team_OnMemberList')){
            return;
        }
        $ownerApp = Abricos::GetApp($team->module);
        $ownerApp->Team_OnMemberList($team, $list);
    }

    private function MemberListMethod(Team $team){
        /** @var TeamMemberList $list */
        $list = $this->InstanceClass('MemberList');
        $rows = TeamQuery::MemberList($this->db, $team->id);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->InstanceClass('Member', $d));
        }

        $this->OnMemberList($team, $list);
        return $list;
    }

    /**
     * @param $filter
     * @return int|TeamMemberList
     */
    public function MemberList($teamid){
        $team = $this->Team($teamid);
        return $team->members;
    }

    public function Invite_IsInvite($type, $ownerid){
        $team = $this->Team($ownerid);
        if (AbricosResponse::IsError($team)){
            return false;
        }
        return true;
    }
}