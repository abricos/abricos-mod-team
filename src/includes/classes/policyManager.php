<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class TeamPolicyItemManager
 */
class TeamPolicyItemManager {

    private static $_cacheActionList = null;

    private $_cachePolicyList = null;

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

                if (!$actionList->IsExistsByPath($ownerModule, $group, $name)){
                    continue;
                }

                $actionList->Add($this->app->InstanceClass('Action', array(
                    'module' => $ownerModule,
                    'group' => $group,
                    'name' => $name
                )));
            }
        }

        if ($policyList->isNewPolicy){
            TeamQuery::PolicyAppendByList($this->app->db, $policyList);
            $this->_cachePolicyList = null;
        }
        if ($actionList->isNewAction){
            TeamQuery::ActionAppendByList($this->app->db, $actionList);
            TeamPolicyItemManager::$_cacheActionList = null;
        }
    }

    private function GetDefaultPolicies(){
        $ownerModule = $this->app->TeamOwnerModule($this->teamid);

        /** @var ITeamOwnerApp $ownerApp */
        $ownerApp = Abricos::GetApp($ownerModule);

        $defPolicies = $ownerApp->Team_GetDefaultPolicy();
        $this->CheckTeamPolicies($defPolicies);


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
        if (!empty(TeamPolicyItemManager::$_cacheActionList)){
            return TeamPolicyItemManager::$_cacheActionList;
        }
        /** @var TeamActionList $list */
        $list = $this->app->InstanceClass('ActionList');
        $rows = TeamQuery::ActionList($this->app->db);
        while (($d = $this->app->db->fetch_array($rows))){
            $list->Add($this->app->InstanceClass('Action', $d));
        }
        return TeamPolicyItemManager::$_cacheActionList = $list;
    }
}
