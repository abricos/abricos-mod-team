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
    private $_cacheUserPolicyList = null;
    private $_cacheUserRoleList = null;

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

        $this->CheckUserRoles();
    }

    public function IsAction($action){
        $this->CheckUserRoles();

        $a = explode(".", $action);
        if (count($a) !== 2){
            return false;
        }

        $group = $a[0];
        $name = $a[1];

        $action = $this->ActionList()->GetByPath($this->ownerModule, $group, $name);
        if (empty($action)){
            throw new Exception('Team action `'.$action.'` not found');
        }

        $userPolicyList = $this->UserPolicyList();
        for ($i = 0; $i < $userPolicyList->Count(); $i++){
            $userPolicy = $userPolicyList->GetByIndex($i);

            $role = $this->RoleList()->GetByPath($userPolicy->policyid, $this->ownerModule, $group);
            if ($role->IsSetCode($action->code)){
                return true;
            }
        }
        return false;
    }

    public function UserAddToPolicy($userid, $policyName){
        $policy = $this->PolicyList()->GetByName($policyName);
        if (empty($policy)){
            return;
        }
        TeamQuery::UserPolicyAppend($this->app->db, $userid, $policy);
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

    public function UserPolicyList(){
        if (!empty($this->_cacheUserPolicyList)){
            return $this->_cacheUserPolicyList;
        }
        /** @var TeamUserPolicyList $list */
        $list = $this->app->InstanceClass('UserPolicyList');
        $rows = TeamQuery::UserPolicyList($this->app->db, $this->teamid, Abricos::$user->id);
        while (($d = $this->app->db->fetch_array($rows))){
            $list->Add($this->app->InstanceClass('UserPolicy', $d));
        }
        return $this->_cacheUserPolicyList = $list;
    }

    public function UserRoleList(){
        if (!empty($this->_cacheUserRoleList)){
            return $this->_cacheUserRoleList;
        }
        /** @var TeamUserRoleList $list */
        $list = $this->app->InstanceClass('UserRoleList');
        $rows = TeamQuery::UserRoleList($this->app->db, $this->teamid, Abricos::$user->id);
        while (($d = $this->app->db->fetch_array($rows))){
            $list->Add($this->app->InstanceClass('UserRole', $d));
        }
        return $this->_cacheUserRoleList = $list;
    }

    /**
     * Идеология:
     * 1. со всех модулей собирается политики по умолчанию
     * 2. поиск новых политик (policy) и их действий (action)
     * 3. если таковые имеются, то добавляются в базу
     * 4. просмотр наличия новых ролей (смотрит на наличие новых действий)
     * 5. если есть новые роли или новые действия, то установка ролям разрешенных действий по умолчанию
     *
     * @param $defPolicies
     */
    private function CheckTeamPolicies($defPolicies){
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

        if ($policyList->isNewItem){
            TeamQuery::PolicyAppendByList($this->app->db, $policyList);
            $this->_cachePolicyList = null;
        }

        if (!$policyList->isNewItem && !$actionList->isNewItem){
            return;
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
    }

    private function CheckUserRoles(){
        $userRoleList = $this->UserRoleList();
        if ($userRoleList->Count() > 0){
            return;
        }

        /** @var ITeamOwnerApp $ownerApp */
        $ownerApp = Abricos::GetApp($this->ownerModule);
        $defPolicies = $ownerApp->Team_GetDefaultPolicy();
        $this->CheckTeamPolicies($defPolicies);

        $userPolicyList = $this->UserPolicyList();
        $roleList = $this->RoleList();

        // TODO: if current user is guest then check TeamPolicy::GUEST

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
                        'userid' => Abricos::$user->id,
                        'module' => $this->ownerModule,
                        'group' => $role->group,
                        'mask' => $role->mask
                    )));
                } else {
                    $userRole->mask = $userRole->mask | $role->mask;
                }
            }
        }

        $this->_cacheUserRoleList = null;
        TeamQuery::UserRoleAppendByList($this->app->db, $userRoleList);
    }
}
