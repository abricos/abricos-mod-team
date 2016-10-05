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

    /**
     * @var TeamApp
     */
    public $app;

    public $teamid;

    public function __construct($teamid){
        $this->teamid = $teamid;

        $this->app = Abricos::GetApp('team');

        $this->GetDefaultPolicies();
    }

    public function IsAction($action){
        $a = explode(".", $action);
        if (count($a) !== 2){
            return false;
        }
    }

    public function UserAddToPolicy($userid, $policyName){
        $policy = $this->PolicyList()->GetByName($policyName);
        if (empty($policy)){
            return;
        }
        TeamQuery::UserPolicyAppend($this->app->db, $userid, $policy);
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

    private function GetDefaultPolicies(){
        $ownerModule = $this->app->TeamOwnerModule($this->teamid);

        /** @var ITeamOwnerApp $ownerApp */
        $ownerApp = Abricos::GetApp($ownerModule);

        $defPolicies = $ownerApp->Team_GetDefaultPolicy();
        $this->CheckTeamPolicies($defPolicies);
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
}
