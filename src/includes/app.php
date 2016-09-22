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
            "TeamListFilter" => "TeamListFilter",
            "TeamSave" => "TeamSave",
            "Member" => "TeamMember",
            "MemberList" => "TeamMemberList",
            "MemberSave" => "TeamMemberSave",
        );
    }

    protected function GetStructures(){
        return 'Team,TeamSave,TeamListFilter,Member,MemberSave';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case "teamSave":
                return $this->TeamSaveToJSON($d->data);
            case 'teamList':
                return $this->TeamListToJSON($d->filter);
            case 'team':
                return $this->TeamToJSON($d->teamid);
            case "memberSave":
                return $this->MemberSaveToJSON($d->data);
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

    public function IsTeamAppend(TeamSave $r){
        if (!$this->manager->IsTeamAppendRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }
        if (!$this->OwnerAppFunctionExist($r->vars->module, 'Team_IsAppend')){
            return AbricosResponse::ERR_SERVER_ERROR;
        }
        $ownerApp = Abricos::GetApp($r->vars->module);
        if (!$ownerApp->Team_IsAppend()){
            return AbricosResponse::ERR_FORBIDDEN;
        }
        return 0;
    }

    public function OnTeamAppend(TeamSave $r){
        if (!$this->OwnerAppFunctionExist($r->vars->module, 'Team_OnTeamAppend')){
            return;
        }
        $ownerApp = Abricos::GetApp($r->vars->module);
        $ownerApp->Team_OnTeamAppend($r);
    }

    public function TeamSave($d){
        /** @var TeamSave $r */
        $r = $this->InstanceClass("TeamSave", $d);

        if (!$this->IsWriteRole()){
            return $r->SetError(AbricosResponse::ERR_FORBIDDEN);
        }

        $vars = $r->vars;

        if (empty($vars->title)){
            $r->AddCode(TeamSave::CODE_ERR_FIELDS, TeamSave::CODE_ERR_TITLE);
        }

        if ($r->IsSetCode(TeamSave::CODE_ERR_FIELDS)){
            return $r->SetError(AbricosResponse::ERR_BAD_REQUEST);
        }

        if ($vars->teamid === 0){
            if (($err = $this->IsTeamAppend($r)) > 0){
                return $r->SetError($err);
            }

            $r->teamid = TeamQuery::TeamAppend($this->db, $r);

            if (empty($r->teamid)){
                return $r->SetError(AbricosResponse::ERR_SERVER_ERROR);
            }

            TeamQuery::MemberAppendByNewTeam($this->db, $r);

            $this->OnTeamAppend($r);
        } else {
        }

        $this->CacheClear();

        return $r;
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

    public function TeamListToJSON($d){
        $res = $this->TeamList($d);
        return $this->ResultToJSON('teamList', $res);
    }

    public function TeamList($d){
        /** @var TeamListFilter $r */
        $r = $this->InstanceClass('TeamListFilter', $d);

        if (!$this->IsViewRole()){
            return $r->SetError(AbricosResponse::ERR_FORBIDDEN);
        }

        $rows = TeamQuery::TeamList($this->db, $r);
        while (($d = $this->db->fetch_array($rows))){
            $r->items->Add($this->InstanceClass('Team', $d));
        }
        return $r;
    }

    /* * * * * * * * * * * * Member * * * * * * * * * */

    public function MemberSaveToJSON($d){
        $res = $this->MemberSave($d);
        return $this->ResultToJSON('memberSave', $res);
    }

    public function IsMemberAppend(Team $team){
        if (!$this->OwnerAppFunctionExist($team->module, 'Team_IsMemberAppend')){
            return AbricosResponse::ERR_SERVER_ERROR;
        }
        $ownerApp = Abricos::GetApp($team->module);
        if (!$ownerApp->Team_IsMemberAppend($team)){
            return AbricosResponse::ERR_FORBIDDEN;
        }
        return 0;
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

    public function MemberSave($d){
        /** @var TeamMemberSave $ret */
        $ret = $this->InstanceClass('MemberSave', $d);

        if (!$this->IsWriteRole()){
            return $ret->SetError(AbricosResponse::ERR_FORBIDDEN);
        }

        $vars = $ret->vars;
        $codes = $ret->codes;

        $team = $this->Team($vars->teamid);

        if (AbricosResponse::IsError($team)){
            return $ret->SetError(AbricosResponse::ERR_BAD_REQUEST);
        }

        if ($vars->memberid === 0){
            if (($err = $this->IsMemberAppend($team)) > 0){
                return $ret->SetError($err);
            }

            /** @var InviteApp $inviteApp */
            $inviteApp = Abricos::GetApp('invite');
            $rUS = $inviteApp->UserSearch($vars->invite);

            if ($rUS->IsSetCode($rUS->codes->ADD_ALLOWED)){


            } else if ($rUS->IsSetCode($rUS->codes->INVITE_ALLOWED)){

            } else {
                return $ret->SetError(AbricosResponse::ERR_BAD_REQUEST);
            }

            $this->MemberSaveMethod($team, array(
                "teamid" => $team->id,
                "userid" => Abricos::$user->id,
                "relUserId" => Abricos::$user->id,
                "isMember" => true,
                "isAdmin" => true,
            ));

        }


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
