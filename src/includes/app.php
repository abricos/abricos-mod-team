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

// TODO: реализовать настройку на кол-во создаваемых сообществ одним пользователем

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
            "UserPolicy" => "TeamUserPolicy",
            "UserPolicyList" => "TeamUserPolicyList",
            "UserRole" => "TeamUserRole",
            "UserRoleList" => "TeamUserRoleList",
            "Plugin" => "TeamPlugin",
            "PluginList" => "TeamPluginList",
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
        return 'Policy,Action,Role,Plugin,Team,TeamSave,TeamListFilter'.
        ',Member,MemberSave,MemberListFilter';
    }

    public function ResponseToJSON($d){
        switch ($d->do){
            case 'appData':
                return $this->ImplodeJSON(
                    $this->ActionListToJSON(),
                    $this->PluginListToJSON()
                );
            case 'actionList':
                return $this->ActionListToJSON();
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
                return $this->MemberToJSON($d->teamid, $d->memberid, $d->policy);
            case 'memberList':
                return $this->MemberListToJSON($d->filter);
        }
        if (isset($d->teamid)){
            $pm = $this->PolicyManager($d->teamid);
            if (empty($pm)){
                return AbricosResponse::ERR_NOT_FOUND;
            }
            return $pm->ResponseToJSON($d);
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
        if ($this->CacheExists('TeamOwnMod', $teamid)){
            return $this->Cache('TeamOwnMod', $teamid);
        }
        $moduleName = TeamQuery::TeamOwnerModule($this->db, $teamid);
        $this->SetCache('TeamOwnMod', $teamid, $moduleName);
        return $moduleName;
    }

    /**
     * @param $teamid
     * @return ITeamOwnerApp
     */
    public function TeamOwnerApp($teamid){
        $ownerModule = $this->TeamOwnerModule($teamid);

        return Abricos::GetApp($ownerModule);
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
    public function PolicyManager($teamid){
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
        $tpm = $this->PolicyManager($teamid);
        if (empty($tpm)){
            return false;
        }
        if (!$tpm->IsAction(TeamAction::TEAM_VIEW)){
            return false;
        }
        return $tpm->IsAction($action);
    }

    public function IsTeamPolicy($teamid, $policyName){
        $tpm = $this->PolicyManager($teamid);
        if (empty($tpm)){
            return false;
        }
        $policy = $tpm->PolicyList()->GetByName($policyName);
        return !empty($policy);
    }

    public function ActionListToJSON(){
        $res = $this->ActionList();
        return $this->ResultToJSON('actionList', $res);
    }

    public function ActionList(){
        if (isset($this->_cache['ActionList'])){
            return $this->_cache['ActionList'];
        }

        if (!$this->IsViewRole()){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        /** @var TeamActionList $list */
        $list = $this->InstanceClass('ActionList');
        $rows = TeamQuery::ActionList($this->db);
        while (($d = $this->db->fetch_array($rows))){
            $list->Add($this->InstanceClass('Action', $d));
        }

        return $this->_cache['ActionList'] = $list;
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

        /**
         * @var string $name
         * @var Ab_Module $module
         */
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

            $tpm = $this->PolicyManager($r->teamid);
            $tpm->CheckTeamPolicies();
            $tpm->UserManager()->AddToPolicy(TeamPolicy::ADMIN);
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
        if (!$this->IsTeamAction($teamid, TeamAction::TEAM_VIEW)){
            return AbricosResponse::ERR_NOT_FOUND;
        }

        if (!isset($this->_cache['Team'])){
            $this->_cache['Team'] = array();
        }

        $d = TeamQuery::Team($this->db, $teamid);
        if (empty($d)){
            print_r($this->db->errorText);
            exit;
        }

        /** @var Team $team */
        $team = $this->InstanceClass('Team', $d);

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

        $rows = TeamQuery::TeamList($this, $r);
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

    public function MemberSave($d){
        /** @var TeamMemberSave $ret */
        $ret = $this->InstanceClass('MemberSave', $d);

        $vars = $ret->vars;
        $teamid = $ret->teamid = $vars->teamid;
        $policyName = $ret->policy = $vars->policy;

        if (!$this->IsTeamPolicy($teamid, $policyName)){
            return $ret->SetError(AbricosResponse::ERR_BAD_REQUEST);
        }

        $ownerModule = $this->TeamOwnerModule($ret->vars->teamid);

        /** @var ITeamOwnerApp $ownerApp */
        $ownerApp = Abricos::GetApp($ownerModule);

        if ($this->OwnerAppFunctionExist($ownerModule, 'Team_OnMemberSave')){
            return $ret->SetError(AbricosResponse::ERR_SERVER_ERROR);
        }

        if ($vars->memberid === 0){

            /** @var InviteApp $inviteApp */
            $inviteApp = Abricos::GetApp('invite');
            $rUS = $inviteApp->UserSearch($vars->invite);

            if (!$this->Invite_IsUserSearch($rUS->vars)){
                return $ret->SetError(AbricosResponse::ERR_BAD_REQUEST);
            }

            // есть ли доступ у текущего пользователя на добавление нового
            // пользователя в политику (группу пользователей)
            if (!$this->IsTeamAction($ret->teamid, $policyName.'.append')){
                return $ret->SetError(AbricosResponse::ERR_FORBIDDEN);
            }

            if ($rUS->IsSetCode($rUS->codes->ADD_ALLOWED)){
                // добавление существующего пользователя

                $ret->memberid = $rUS->userid;
                $um = $this->PolicyManager($teamid)->UserManager($rUS->userid);

                if ($um->IsInPolicy($policyName)){
                    $ret->AddCode(TeamMemberSave::CODE_IS_IN_POLICY);
                    return $ret;
                }

                $um->AddToPolicy($policyName);

                if ($um->IsMember()){
                    $ret->AddCode(TeamMemberSave::CODE_IS_INSIDER);
                } else {
                    $ret->AddCode(TeamMemberSave::CODE_IS_STRANGER);

                    if (!$um->IsInPolicy(TeamPolicy::INVITE)){
                        $ret->AddCode(TeamMemberSave::CODE_NEED_NOTIFY);
                        $um->AddToPolicy(TeamPolicy::INVITE);
                    }
                }

                $ownerApp->Team_OnMemberAppend($ret);

            } else if ($rUS->IsSetCode($rUS->codes->INVITE_ALLOWED)){

                /** @var InviteCreate $rCreate */
                $rCreate = $inviteApp->Create($rUS, $d);

                if (!$rCreate->IsSetCode(InviteCreate::CODE_OK)){
                    return $ret->SetError(AbricosResponse::ERR_SERVER_ERROR);
                }

                $ret->memberid = $rCreate->userid;
                $ret->policy = $policyName;

                $um = $this->PolicyManager($ret->teamid)->UserManager($rCreate->userid);
                $um->AddToPolicy(array(
                    TeamPolicy::INVITE,
                    $policyName
                ));

                $ret->AddCode(TeamMemberSave::CODE_IS_STRANGER);
                $ret->AddCode(TeamMemberSave::CODE_NEED_NOTIFY);

                $ownerApp->Team_OnMemberInvite($ret, $rCreate);
            } else {
                return $ret->SetError(AbricosResponse::ERR_BAD_REQUEST);
            }
        } else {
            if (!$this->IsTeamAction($ret->teamid, $policyName.'.update')){
                return $ret->SetError(AbricosResponse::ERR_FORBIDDEN);
            }

            $member = $this->Member($ret->teamid, $vars->memberid, $policyName);
            if (AbricosResponse::IsError($member)){
                return $ret->SetError(AbricosResponse::ERR_BAD_REQUEST);
            }

            $ret->memberid = $member->id;

            $ownerApp->Team_OnMemberUpdate($ret);
        }

        $ret->AddCode(TeamMemberSave::CODE_OK);

        return $ret;
    }

    public function MemberToJSON($teamid, $memberid, $policyName){
        $res = $this->Member($teamid, $memberid, $policyName);
        return $this->ResultToJSON('member', $res);
    }

    public function Member($teamid, $memberid, $policyName){
        $action = $policyName.".view";
        if (!$this->IsTeamAction($teamid, $action)){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        $d = TeamQuery::Member($this->db, $teamid, $memberid, $policyName);
        if (empty($d)){
            return AbricosResponse::ERR_NOT_FOUND;
        }
        /** @var TeamMember $member */
        $member = $this->InstanceClass('Member', $d);

        $ownerApp = $this->TeamOwnerApp($teamid);
        $ownerApp->Team_OnMember($member, $policyName);

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

        $teamid = $filter->vars->teamid;
        $policyName = $filter->vars->policy;
        $action = $policyName.".list";

        if (!$this->IsTeamAction($teamid, $action)){
            return $filter->SetError(AbricosResponse::ERR_FORBIDDEN);
        }

        $rows = TeamQuery::MemberList($this->db, $filter);
        while (($d = $this->db->fetch_array($rows))){
            /** @var TeamMember $member */
            $member = $this->InstanceClass('Member', $d);

            $filter->items->Add($member);
        }

        $ownerApp = $this->TeamOwnerApp($teamid);
        $ownerApp->Team_OnMemberList($filter);

        return $filter;
    }

    /**
     * @param InviteUserSearchVars $rUSVars
     * @return bool
     */
    public function Invite_IsUserSearch($rUSVars){
        $teamid = $rUSVars->owner->ownerid;
        $policyName = $rUSVars->owner->type;

        if (!$this->IsTeamPolicy($teamid, $policyName)){
            return false;
        }

        return $this->IsTeamAction($teamid, $policyName.'.append');
    }

}
