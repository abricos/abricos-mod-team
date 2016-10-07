<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class TeamPolicyManager
 */
class TeamPolicyManager {

    private static $_cacheActionList = null;

    private $_cachePolicyList = null;
    private $_cacheRoleList = null;
    private $_cacheUserManager = array();

    /**
     * @var TeamApp
     */
    public $app;

    public $teamid;
    public $ownerModule;

    public function __construct($teamid){
        $this->teamid = $teamid;
        $this->app = Abricos::GetApp('team');

        $this->ownerModule = $this->app->TeamOwnerModule($this->teamid);
    }

    public function IsAction($action, $userid = -1){
        if ($userid === -1){
            $userid = Abricos::$user->id;
        }

        $um = $this->UserManager($userid);
        $ret = $um->IsAction($action);

        if ($ret){
            return true;
        }

        // этот пользователь не вхож в сообщество, проверка роли гостя
        if ($userid > 0){
            $umGuest = $this->UserManager(0);
            return $umGuest->IsAction($action);
        }

        return false;
    }

    /**
     * @param int $userid
     * @return TeamUserPolicyManager
     */
    public function UserManager($userid = -1){
        if ($userid === -1){
            $userid = Abricos::$user->id;
        }

        if (isset($this->_cacheUserManager[$userid])){
            return $this->_cacheUserManager[$userid];
        }

        $um = new TeamUserPolicyManager($this, $userid);
        return $this->_cacheUserManager[$userid] = $um;
    }

    public function ResponseToJSON($d){
        if (!$this->IsAction(TeamAction::ROLE_UPDATE)){
            return AbricosResponse::ERR_FORBIDDEN;
        }

        switch ($d->do){
            case 'policies':
                return $this->PoliciesToJSON();
            case 'actionList':
                return $this->ActionListToJSON();
            case 'policyList':
                return $this->PolicyListToJSON();
            case 'roleList':
                return $this->RoleListToJSON();
        }
        return null;
    }

    private function PoliciesToJSON(){
        return $this->app->ImplodeJSON(array(
            $this->PolicyListToJSON(),
            $this->ActionListToJSON(),
            $this->RoleListToJSON()
        ));
    }

    private function RoleListToJSON(){
        $list = $this->RoleList();
        return $this->app->ResultToJSON('roleList', $list);
    }

    /**
     * @return TeamRoleList
     */
    public function RoleList(){
        if (!empty($this->_cacheRoleList)){
            return $this->_cacheRoleList;
        }

        /** @var TeamRoleList $list */
        $list = $this->app->InstanceClass('RoleList');
        $rows = TeamQuery::RoleList($this->app->db, $this->teamid);
        while (($d = $this->app->db->fetch_array($rows))){
            $list->Add($this->app->InstanceClass('Role', $d));
        }
        return $this->_cacheRoleList = $list;
    }

    private function PolicyListToJSON(){
        $list = $this->PolicyList();
        return $this->app->ResultToJSON('policyList', $list);
    }

    /**
     * @return TeamPolicyList
     */
    public function PolicyList(){
        if (!empty($this->_cachePolicyList)){
            return $this->_cachePolicyList;
        }

        /** @var TeamPolicyList $list */
        $list = $this->app->InstanceClass('PolicyList');
        $rows = TeamQuery::PolicyList($this->app->db, $this->teamid);
        while (($d = $this->app->db->fetch_array($rows))){
            $list->Add($this->app->InstanceClass('Policy', $d));
        }
        return $this->_cachePolicyList = $list;
    }

    private function ActionListToJSON(){
        $list = $this->ActionList();
        return $this->app->ResultToJSON('actionList', $list);
    }

    /**
     * @return TeamActionList
     */
    public function ActionList(){
        if (!empty(TeamPolicyManager::$_cacheActionList)){
            return TeamPolicyManager::$_cacheActionList;
        }
        /** @var TeamActionList $list */
        $list = $this->app->InstanceClass('ActionList');
        $rows = TeamQuery::ActionList($this->app->db);
        while (($d = $this->app->db->fetch_array($rows))){
            $list->Add($this->app->InstanceClass('Action', $d));
        }
        return TeamPolicyManager::$_cacheActionList = $list;
    }

    /**
     * Идеология:
     * 1. со всех модулей собирается политики по умолчанию
     * 2. поиск новых политик (policy) и их действий (action)
     * 3. если таковые имеются, то добавляются в базу
     * 4. просмотр наличия новых ролей (смотрит на наличие новых действий)
     * 5. если есть новые роли или новые действия, то установка ролям разрешенных действий по умолчанию
     */
    public function CheckTeamPolicies(){
        /** @var ITeamOwnerApp $ownerApp */
        $ownerApp = Abricos::GetApp($this->ownerModule);
        $defPolicies = $ownerApp->Team_GetDefaultPolicy();

        if (!is_array($defPolicies)){
            return;
        }

        $ownerModule = $this->app->TeamOwnerModule($this->teamid);

        $actionList = $this->ActionList();
        $policyList = $this->PolicyList();

        foreach ($defPolicies as $policy => $actions){
            if (!$policyList->IsExists($policy)){
                $policyList->Add($this->app->InstanceClass('Policy', array(
                    'teamid' => $this->teamid,
                    'name' => $policy,
                    'isSys' => true
                )));
            }

            $count = count($actions);
            for ($i = 0; $i < $count; $i++){
                $a = explode(".", $actions[$i]);
                $group = $a[0];
                $name = $a[1];

                if ($actionList->IsExistsByPath($ownerModule, $group, $name)){
                    continue;
                }

                $actionList->Add($this->app->InstanceClass('Action', array(
                    'module' => $ownerModule,
                    'group' => $group,
                    'name' => $name
                )));
            }
        }

        if (!$policyList->isNewItem && !$actionList->isNewItem){
            return;
        }

        if ($policyList->isNewItem){
            TeamQuery::PolicyAppendByList($this->app->db, $policyList);
            $this->_cachePolicyList = null;
        }

        $policyList = $this->PolicyList();
        $roleList = $this->RoleList();

        for ($i = 0; $i < $policyList->Count(); $i++){
            $policy = $policyList->GetByIndex($i);

            for ($ii = 0; $ii < $actionList->Count(); $ii++){
                $action = $actionList->GetByIndex($ii);

                $role = $roleList->GetByPath($policy->id, $action->module, $action->group);

                if (empty($role)){
                    /** @var TeamRole $role */
                    $role = $this->app->InstanceClass('Role', array(
                        'policyid' => $policy->id,
                        'module' => $ownerModule,
                        'group' => $action->group
                    ));
                    $roleList->Add($role);

                    $defActions = $defPolicies[$policy->name];

                    $count = count($defActions);
                    for ($ai = 0; $ai < $count; $ai++){
                        $a = explode(".", $defActions[$ai]);
                        $group = $a[0];
                        $name = $a[1];

                        if ($role->group !== $group){
                            continue;
                        }

                        $defAction = $actionList->GetByPath($ownerModule, $group, $name);
                        $role->AddCode($defAction->code);
                    }
                } else if ($action->isNewItem && !$role->isNewItem){
                    $defActions = $defPolicies[$policy->name];
                    $count = count($defActions);
                    for ($ai = 0; $ai < $count; $ai++){
                        $a = explode(".", $defActions[$ai]);
                        $group = $a[0];
                        $name = $a[1];

                        if ($role->group === $group && $action->name === $name){
                            $role->AddCode($action->code);
                            TeamQuery::RoleUpdate($this->app->db, $role);
                            break;
                        }
                    }
                }
            }
        }

        TeamQuery::ActionAppendByList($this->app->db, $actionList);

        if ($roleList->isNewItem){
            TeamQuery::RoleAppendByList($this->app->db, $roleList);
        }

        TeamPolicyManager::$_cacheActionList = null;
        $this->_cacheRoleList = null;

        // remove all cache user roles in team
        TeamQuery::UserRoleClean($this->app->db, $this->teamid);

        $um = $this->UserManager(0);
        $um->AddToPolicy(TeamPolicy::GUEST);
    }

}

class TeamUserPolicyManager {

    private $_cacheUserPolicyList = null;
    private $_cacheUserRoleList = null;

    /**
     * @var TeamPolicyManager
     */
    public $tpm;
    public $app;
    public $teamid;
    public $userid;

    public function __construct(TeamPolicyManager $tpm, $userid){
        $this->tpm = $tpm;
        $this->teamid = $tpm->teamid;
        $this->app = $tpm->app;
        $this->userid = $userid;
    }

    public function IsAction($actionKey){
        $a = explode(".", $actionKey);
        if (count($a) !== 2){
            return false;
        }
        $group = $a[0];
        $name = $a[1];

        $ownerModule = $this->tpm->ownerModule;

        $action = $this->tpm->ActionList()->GetByPath($ownerModule, $group, $name);
        if (empty($action)){
            throw new Exception('Team action `'.$actionKey.'` not found');
        }

        $this->CheckUserRoles();
        $userRole = $this->UserRoleList()->GetByPath($ownerModule, $group);
        if (empty($userRole)){
            return false; // what !?!
        }
        return $userRole->IsSetCode($action->code);
    }

    public function AddToPolicy($policyNames){
        if (!is_array($policyNames)){
            $policyNames = array($policyNames);
        }

        $policyList = $this->tpm->PolicyList();

        for ($i = 0; $i < count($policyNames); $i++){
            $policy = $policyList->GetByName($policyNames[$i]);
            if (empty($policy)){
                continue;
            }
            TeamQuery::UserPolicyAppend($this->app->db, $this->userid, $policy);
        }

        TeamQuery::UserRoleClean($this->app->db, $this->teamid, $this->userid);

        $this->_cacheUserPolicyList = null;
        $this->_cacheUserRoleList = null;

        $this->CheckUserRoles();
    }

    private function CheckUserRoles(){
        $userRoleList = $this->UserRoleList();
        if ($userRoleList->Count() > 0){
            return;
        }

        $userRoleList = $this->UserRoleList();
        $userPolicyList = $this->UserPolicyList();
        $roleList = $this->tpm->RoleList();

        for ($i = 0; $i < $userPolicyList->Count(); $i++){
            $userPolicy = $userPolicyList->GetByIndex($i);

            for ($ii = 0; $ii < $roleList->Count(); $ii++){
                $role = $roleList->GetByIndex($ii);
                if ($role->policyid !== $userPolicy->policyid){
                    continue;
                }
                $userRole = $userRoleList->GetByPath($role->module, $role->group);

                if (empty($userRole)){
                    $userRoleList->Add($this->app->InstanceClass('UserRole', array(
                        'teamid' => $this->teamid,
                        'userid' => $this->userid,
                        'module' => $this->tpm->ownerModule,
                        'group' => $role->group,
                        'mask' => $role->mask
                    )));
                } else {
                    $userRole->mask = $userRole->mask | $role->mask;
                }
            }
        }

        TeamQuery::UserRoleAppendByList($this->app->db, $userRoleList);

        $this->_cacheUserRoleList = null;
    }

    /**
     * @return TeamUserPolicyList
     */
    public function UserPolicyList(){
        if (!empty($this->_cacheUserPolicyList)){
            return $this->_cacheUserPolicyList;
        }

        /** @var TeamUserPolicyList $list */
        $list = $this->app->InstanceClass('UserPolicyList');
        $rows = TeamQuery::UserPolicyList($this->app->db, $this->teamid, $this->userid);
        while (($d = $this->app->db->fetch_array($rows))){
            $list->Add($this->app->InstanceClass('UserPolicy', $d));
        }
        return $this->_cacheUserPolicyList = $list;
    }

    /**
     * @return TeamUserRoleList
     */
    public function UserRoleList(){
        if (!empty($this->_cacheUserRoleList)){
            return $this->_cacheUserRoleList;
        }
        /** @var TeamUserRoleList $list */
        $list = $this->app->InstanceClass('UserRoleList');
        $rows = TeamQuery::UserRoleList($this->app->db, $this->teamid, $this->userid);
        while (($d = $this->app->db->fetch_array($rows))){
            $list->Add($this->app->InstanceClass('UserRole', $d));
        }

        return $this->_cacheUserRoleList = $list;
    }


}
