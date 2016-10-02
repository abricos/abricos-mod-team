<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'interfaces.php';
require_once 'policies.php';
require_once 'classes/policyManager.php';

/**
 * Class TeamApp
 *
 * @property TeamManager $manager
 */
class TeamApp extends AbricosApplication {

    protected function GetClasses(){
        return array(
            "Action" => "TeamActionItem",
            "ActionList" => "TeamActionList",
            "Policy" => "TeamPolicyItem",
            "PolicyList" => "TeamPolicyList",
            "Role" => "TeamRole",
            "RoleList" => "TeamRoleList",
            "Plugin" => "TeamPlugin",
            "PluginList" => "TeamPluginList",
            "TeamUserRole" => "TeamUserRole",
            "TeamUserRoleList" => "TeamUserRoleList",
            "Team" => "Team",
            "TeamList" => "TeamList",
            "TeamListFilter" => "TeamListFilter",
            "TeamSave" => "TeamSave",
            "Member" => "TeamMember",
            "MemberList" => "TeamMemberList",
            "MemberListFilter" => "TeamMemberListFilter",
            "MemberSave" => "TeamMemberSave",
        );
    }

    protected function GetStructures(){
        return 'Policy,Action,Role,Plugin,TeamUserRole,Team,TeamSave,TeamListFilter'.
        ',Member,MemberSave,MemberListFilter';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case 'pluginList':
                return $this->PluginListToJSON();
            case "teamSave":
                return $this->TeamSaveToJSON($d->data);
            case 'teamList':
                return $this->TeamListToJSON($d->filter);
            case 'team':
                return $this->TeamToJSON($d->teamid);
            case "memberSave":
                return $this->MemberSaveToJSON($d->data);
            case 'member':
                return $this->MemberToJSON($d->teamid, $d->memberid);
            case 'memberList':
                return $this->MemberListToJSON($d->filter);
            case 'policies':
                return $this->PoliciesToJSON($d->teamid);
            case 'actionList':
                return $this->ActionListToJSON($d->teamid);
            case 'policyList':
                return $this->PolicyListToJSON($d->teamid);
        }
        return null;
    }

    public function IsAdminRole(){
        return $this->manager->IsAdminRole();
    }

    public function IsTeamAppendRole(){
        return $this->manager->IsTeamAppendRole();
    }

    public function IsWriteRole(){
        return $this->manager->IsWriteRole();
    }

    public function IsViewRole(){
        return $this->manager->IsViewRole();
    }

    /**
     * @param $teamid
     * @return string
     */
    public function TeamOwnerModule($teamid){
        if (isset($this->_cache['TeamOwnMod'][$teamid])){
            return $this->_cache['TeamOwnMod'][$teamid];
        }
        if (!isset($this->_cache['TeamOwnMod'])){
            $this->_cache['TeamOwnMod'] = array();
        }
        $moduleName = TeamQuery::TeamOwnerModule($this->db, $teamid);
        return $this->_cache['TeamOwnMod'][$teamid] = $moduleName;
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

    public function IsTeamExists($teamid){
        $module = $this->TeamOwnerModule($teamid);
        return !empty($module);
    }

    /**
     * @param $teamid
     * @return TeamPolicyManager
     */
    public function TeamPolicyManager($teamid){
        if (!$this->IsTeamExists($teamid)){
            return null;
        }
        if (isset($this->_cache['TPM'][$teamid])){
            $tpm = $this->_cache['TPM'][$teamid];
        } else {
            if (!isset($this->_cache['TPM'])){
                $this->_cache['TPM'] = array();
            }
            $tpm = new TeamPolicyManager($teamid);
            $this->_cache['TPM'][$teamid] = $tpm;
        }
        return $tpm;
    }

    public function IsTeamAction($teamid, $action){
        $tpm = $this->TeamPolicyManager($teamid);
        if (empty($tpm)){
            return false;
        }
        return $tpm->IsAction($action);
    }

    public function PoliciesToJSON($teamid){
        return $this->ImplodeJSON(array(
           $this->PolicyListToJSON($teamid),
           $this->ActionListToJSON($teamid)
        ));
    }

    public function PolicyListToJSON($teamid){
        // TODO: check user roles

        $tpm = $this->TeamPolicyManager($teamid);
        if (empty($tpm)){
            return AbricosResponse::ERR_NOT_FOUND;
        }

        $list = $tpm->PolicyList();

        return $this->ResultToJSON('policyList', $list);
    }

    public function ActionListToJSON($teamid){
        $tpm = $this->TeamPolicyManager($teamid);
        if (empty($tpm)){
            return AbricosResponse::ERR_NOT_FOUND;
        }

        $list = $tpm->ActionList();

        return $this->ResultToJSON('actionList', $list);
    }

    public function PluginListToJSON(){
        $res = $this->PluginList();
        return $this->ResultToJSON('pluginList', $res);
    }

    public function PluginList(){
        if (!$this->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        /** @var TeamPluginList $list */
        $list = $this->InstanceClass('PluginList');

        $modules = Abricos::$modules->RegisterAllModule();

        foreach ($modules as $name => $module){
            if (!method_exists($module, 'Team_IsPlugin')){
                continue;
            }
            if (!$module->Team_IsPlugin()){
                continue;
            }
            $man = $module->GetManager();

            if (!method_exists($man, 'Team_Plugin')){
                continue;
            }

            /** @var TeamPlugin $plugin */
            $plugin = $man->Team_Plugin();

            if (empty($plugin)){
                continue;
            }

            if (!($plugin instanceof TeamPlugin)){
                $plugin = $this->InstanceClass('Plugin', $plugin);
            }

            $plugin->id = $name;

            $list->Add($plugin);
        }
        return $list;
    }

    /**
     * @param int $teamid
     * @return TeamUserRole
     */
    public function TeamUserRole($teamid){
        if (isset($this->_cache['TeamUserRole'][$teamid])){
            return $this->_cache['TeamUserRole'][$teamid];
        }
        if (!isset($this->_cache['TeamUserRole'])){
            $this->_cache['TeamUserRole'] = array();
        }

        $d = TeamQuery::TeamUserRole($this->db, $teamid);
        /** @var TeamUserRole $userRole */
        $userRole = $this->InstanceClass('TeamUserRole', $d);

        return $this->_cache['TeamUserRole'][$teamid] = $userRole;
    }

    /**
     * @param $teamids
     * @return TeamUserRoleList
     */
    public function TeamUserRoleList($teamids){
        /** @var TeamUserRoleList $list */
        $list = $this->InstanceClass('TeamUserRoleList');

        $rows = TeamQuery::TeamUserRole($this->db, $teamids);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->InstanceClass('TeamUserRole', $d));
        }
        return $list;
    }

    public function TeamSaveToJSON($d){
        $res = $this->TeamSave($d);
        return $this->ResultToJSON('teamSave', $res);
    }

    public function TeamSave($d){
        /** @var TeamSave $r */
        $r = $this->InstanceClass("TeamSave", $d);
        $vars = $r->vars;

        /** @var ITeamOwnerApp $ownerApp */
        $ownerApp = Abricos::GetApp($vars->module);
        if (empty($ownerApp)){
            return $r->SetError(AbricosResponse::ERR_BAD_REQUEST);
        }

        if (empty($vars->title)){
            $r->AddCode(TeamSave::CODE_ERR_FIELDS, TeamSave::CODE_ERR_TITLE);
        }

        if ($r->IsSetCode(TeamSave::CODE_ERR_FIELDS)){
            return $r->SetError(AbricosResponse::ERR_BAD_REQUEST);
        }

        if ($vars->teamid === 0){
            if (!$ownerApp->IsTeamAppendRole()){
                return $r->SetError(AbricosResponse::ERR_FORBIDDEN);
            }

            $r->teamid = TeamQuery::TeamAppend($this->db, $r);

            if (empty($r->teamid)){
                return $r->SetError(AbricosResponse::ERR_SERVER_ERROR);
            }

            $memberid = TeamQuery::MemberAppendByNewTeam($this->db, $r);

            $tmp = $this->TeamPolicyManager($r->teamid);
            $tmp->AddMemberToPolicy($memberid, TeamPolicy::ADMIN);
        } else {
            $team = $this->Team($vars->teamid);
            if (AbricosResponse::IsError($team)){
                return $team;
            }

            if (!$team->userRole->IsAdmin()){
                return $r->SetError(AbricosResponse::ERR_FORBIDDEN);
            }

            TeamQuery::TeamUpdate($this->db, $r);
            $r->teamid = $team->id;
        }

        $ownerApp->Team_OnTeamSave($r);

        $this->CacheClear();

        return $r;
    }

    public function TeamToJSON($teamid){
        $res = $this->Team($teamid);
        return $this->ResultToJSON('team', $res);
    }

    /**
     * @param int $teamid
     * @return int|Team
     */
    public function Team($teamid){
        $teamid = intval($teamid);
        if (isset($this->_cache['Team'][$teamid])){
            return $this->_cache['Team'][$teamid];
        }

        $this->IsTeamAction($teamid, TeamAction::TEAM_VIEW);

        $userRole = $this->TeamUserRole($teamid);

        if (!$userRole->TeamIsExist()){
            return AbricosResponse::ERR_NOT_FOUND;
        }
        if (!$userRole->IsView()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        if (!isset($this->_cache['Team'])){
            $this->_cache['Team'] = array();
        }

        $d = TeamQuery::Team($this->db, $teamid);

        /** @var Team $team */
        $team = $this->InstanceClass('Team', $d);
        $team->userRole = $userRole;

        $this->_cache['Team'][$teamid] = $team;

        /** @var ITeamOwnerApp $ownerApp */
        $ownerApp = Abricos::GetApp($team->module);

        $ownerApp->Team_OnTeam($team);

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

        $userRoleList = $this->TeamUserRoleList($r->items->ToArray('id'));

        $count = $userRoleList->Count();
        for ($i = 0; $i < $count; $i++){
            $userRole = $userRoleList->GetByIndex($i);
            $team = $r->items->Get($userRole->id);
            $team->userRole = $userRole;
        }

        return $r;
    }

    /* * * * * * * * * * * * Member * * * * * * * * * */

    public function MemberSaveToJSON($d){
        $res = $this->MemberSave($d);
        return $this->ResultToJSON('memberSave', $res);
    }

    public function MemberSave($d){
        /** @var TeamMemberSave $ret */
        $ret = $this->InstanceClass('MemberSave', $d);

        if (!$this->IsWriteRole()){
            return $ret->SetError(AbricosResponse::ERR_FORBIDDEN);
        }

        $vars = $ret->vars;
        $userRole = $this->TeamUserRole($vars->teamid);

        if (!$userRole->IsAdmin()){
            return $ret->SetError(AbricosResponse::ERR_FORBIDDEN);
        }

        $ret->teamid = $vars->teamid;

        /** @var ITeamOwnerApp $ownerApp */
        $ownerApp = Abricos::GetApp($userRole->module);

        if ($this->OwnerAppFunctionExist($userRole->module, 'Team_OnMemberSave')){
            return $ret->SetError(AbricosResponse::ERR_SERVER_ERROR);
        }

        if ($vars->memberid === 0){
            /** @var InviteApp $inviteApp */
            $inviteApp = Abricos::GetApp('invite');
            $rUS = $inviteApp->UserSearch($vars->invite);

            if ($rUS->IsSetCode($rUS->codes->ADD_ALLOWED)){

            } else if ($rUS->IsSetCode($rUS->codes->INVITE_ALLOWED)){
                /** @var InviteCreate $rCreate */
                $rCreate = $inviteApp->Create($rUS, $d);

                if (!$rCreate->IsSetCode(InviteCreate::CODE_OK)){
                    return $ret->SetError(AbricosResponse::ERR_SERVER_ERROR);
                }

                $ret->userid = $rCreate->userid;
                $ret->memberid = TeamQuery::MemberInviteNewUser($this->db, $ret);

                $ownerApp->Team_OnMemberInvite($ret, $rCreate);
            } else {
                return $ret->SetError(AbricosResponse::ERR_BAD_REQUEST);
            }
        } else {
            $member = $this->Member($vars->teamid, $vars->memberid);
            if (AbricosResponse::IsError($member)){
                return $ret->SetError(AbricosResponse::ERR_BAD_REQUEST);
            }

            $ret->memberid = $member->id;
            $ret->userid = $member->userid;

            $ownerApp->Team_OnMemberUpdate($ret);
        }

        return $ret;
    }

    public function MemberToJSON($teamid, $memberid){
        $res = $this->Member($teamid, $memberid);
        return $this->ResultToJSON('member', $res);
    }

    public function Member($teamid, $memberid){
        $userRole = $this->TeamUserRole($teamid);
        if (!$userRole->TeamIsExist()){
            return AbricosResponse::ERR_NOT_FOUND;
        }
        if (!$userRole->IsView()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $d = TeamQuery::Member($this->db, $teamid, $memberid);
        if (empty($d)){
            return AbricosResponse::ERR_NOT_FOUND;
        }
        /** @var TeamMember $member */
        $member = $this->InstanceClass('Member', $d);

        /** @var ITeamOwnerApp $ownerApp */
        $ownerApp = Abricos::GetApp($member->module);
        $ownerApp->Team_OnMember($member);

        return $member;
    }

    public function MemberListToJSON($d){
        $res = $this->MemberList($d);
        return $this->ResultToJSON('memberList', $res);
    }

    /**
     * @param object|array|TeamMemberListFilter $filter
     * @return TeamMemberListFilter
     */
    public function MemberList($filter){
        if (!($filter instanceof TeamMemberListFilter)){
            /** @var TeamMemberListFilter $filter */
            $filter = $this->InstanceClass('MemberListFilter', $filter);
        }

        if (!$this->IsViewRole()){
            return $filter->SetError(AbricosResponse::ERR_FORBIDDEN);
        }

        switch ($filter->vars->method){
            case 'team':
            case 'iInTeams':
                break;
            default:
                return $filter->SetError(
                    AbricosResponse::ERR_BAD_REQUEST,
                    TeamMemberListFilter::CODE_BAD_METHOD
                );
        }

        $arr = array();

        $rows = TeamQuery::MemberList($this->db, $filter);
        while (($d = $this->db->fetch_array($rows))){
            /** @var TeamMember $member */
            $member = $this->InstanceClass('Member', $d);

            if (!isset($arr[$member->module])){
                $arr[$member->module] = $this->InstanceClass('MemberList');
            }
            $arr[$member->module]->Add($member);

            $filter->items->Add($member);
        }

        foreach ($arr as $module => $list){
            /** @var ITeamOwnerApp $ownerApp */
            $ownerApp = Abricos::GetApp($module);
            $ownerApp->Team_OnMemberList($list);
        }

        return $filter;
    }

    /**
     * @param InviteUserSearchVars $rUSVars
     * @return bool
     */
    public function Invite_IsUserSearch($rUSVars){
        $owner = $rUSVars->owner;
        $userRole = $this->TeamUserRole($owner->ownerid);
        return $userRole->IsAdmin();
    }

}
