<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'policies.php';

/**
 * Class TeamActionItem
 *
 * @property int $id Action ID
 * @property string $module
 * @property string $group
 * @property string $name
 * @property int $code
 */
class TeamActionItem extends AbricosModel {
    protected $_structModule = 'team';
    protected $_structName = 'Action';

    public $isNewItem = false;
}

/**
 * Class TeamActionList
 *
 * @method TeamActionItem Get(int $id)
 * @method TeamActionItem GetByIndex(int $index)
 */
class TeamActionList extends AbricosModelList {

    private $_actionList = array();

    private $_maxid = 0;

    public $isNewItem = false;

    /**
     * @param TeamActionItem $item
     */
    public function Add($item){
        if ($item->id === 0){
            if ($this->IsExistsByPath($item->module, $item->group, $item->name)){
                return;
            }

            $item->isNewItem = true;
            $item->id = $this->_maxid + 1;

            $count = $this->CountInGroup($item->module, $item->group);
            $item->code = 1 << $count;
            $this->isNewItem = true;
        }

        $this->_maxid = max($this->_maxid, $item->id);

        parent::Add($item);

        $module = $item->module;
        $group = $item->group;
        $name = $item->name;

        if (!isset($this->_actionList[$module])){
            $this->_actionList[$module] = array();
        }
        if (!isset($this->_actionList[$module][$group])){
            $this->_actionList[$module][$group] = array();
        }
        $this->_actionList[$module][$group][$name] = $item;
    }

    public function CountInGroup($module, $group){
        if (!isset($this->_actionList[$module][$group])){
            return 0;
        }
        return count($this->_actionList[$module][$group]);
    }

    public function IsExistsByPath($module, $group, $name){
        return isset($this->_actionList[$module][$group][$name]);
    }

    /**
     * @param $module
     * @param $group
     * @param $name
     * @return TeamActionItem
     */
    public function GetByPath($module, $group, $name){
        if (!isset($this->_actionList[$module][$group][$name])){
            return null;
        }
        return $this->_actionList[$module][$group][$name];
    }
}

/**
 * Class TeamPolicyItem
 *
 * @property int $teamid
 * @property string $name
 * @property string $title
 * @property string $descript
 * @property bool $isSys
 */
class TeamPolicyItem extends AbricosModel {
    protected $_structModule = 'team';
    protected $_structName = 'Policy';

    public $isNewItem = false;
}

/**
 * Class TeamPolicyList
 *
 * @method TeamPolicyItem Get(int $id)
 * @method TeamPolicyItem GetByIndex(int $index)
 */
class TeamPolicyList extends AbricosModelList {

    private $_policyList = array();

    private $_maxid = 0;

    public $isNewItem = false;

    /**
     * @param TeamPolicyItem $item
     */
    public function Add($item){
        if ($item->id === 0){
            $item->id = $this->_maxid + 1;

            $item->isNewItem = true;
            $this->isNewItem = true;
        }

        $this->_maxid = max($this->_maxid, $item->id);

        parent::Add($item);

        $this->_policyList[$item->name] = $item;
    }

    public function IsExists($name){
        return !empty($this->_policyList[$name]);
    }

    /**
     * @param $name
     * @return TeamPolicyItem
     */
    public function GetByName($name){
        return isset($this->_policyList[$name]) ? $this->_policyList[$name] : null;
    }
}

/**
 * Class TeamRole
 *
 * @property int $id Role ID
 * @property int $policyid
 * @property string $module
 * @property string $group
 * @property int $mask
 */
class TeamRole extends AbricosModel {
    protected $_structModule = 'team';
    protected $_structName = 'Role';

    public $isNewItem;

    public function AddCode($code){
        $this->mask |= $code;
    }

    public function IsSetCode($code){
        return $this->mask & $code;
    }
}

/**
 * Class TeamRoleList
 *
 * @method TeamRole Get(int $id)
 * @method TeamRole GetByIndex(int $i)
 */
class TeamRoleList extends AbricosModelList {

    private $_roles = array();

    private $_maxid = 0;

    public $isNewItem = false;

    /**
     * @param TeamRole $item
     */
    public function Add($item){
        if ($item->id === 0){
            $item->id = $this->_maxid + 1;

            $item->isNewItem = true;
            $this->isNewItem = true;
        }

        $this->_maxid = max($this->_maxid, $item->id);

        parent::Add($item);

        if (!isset($this->_roles[$item->policyid])){
            $this->_roles[$item->policyid] = array();
        }
        if (!isset($this->_roles[$item->policyid][$item->module])){
            $this->_roles[$item->policyid][$item->module] = array();
        }

        $this->_roles[$item->policyid][$item->module] [$item->group] = $item;
    }

    /**
     * @param $policyid
     * @param $ownerModule
     * @param $group
     * @return TeamRole
     */
    public function GetByPath($policyid, $ownerModule, $group){
        if (!isset($this->_roles[$policyid][$ownerModule][$group])){
            return null;
        }
        return $this->_roles[$policyid][$ownerModule][$group];
    }
}

/**
 * Class TeamUserPolicy
 *
 * @property int $userid
 * @property int $policyid
 */
class TeamUserPolicy extends AbricosModel {
    protected $_structModule = 'team';
    protected $_structName = 'UserPolicy';
}

/**
 * Class TeamUserPolicyList
 *
 * @method TeamUserPolicy Get(int $id)
 * @method TeamUserPolicy GetByIndex(int $i)
 */
class TeamUserPolicyList extends AbricosModelList {
}

/**
 * Class TeamUserRole
 *
 * @property int $teamid
 * @property int $userid
 * @property string $module
 * @property string $group
 * @property int $mask
 */
class TeamUserRole extends AbricosModel {
    protected $_structModule = 'team';
    protected $_structName = 'UserRole';

    public $isNewItem = false;

    public function IsSetCode($code){
        return $this->mask & $code;
    }
}

/**
 * Class TeamUserRoleList
 *
 * @method TeamUserRole Get(int $id)
 * @method TeamUserRole GetByIndex(int $index)
 */
class TeamUserRoleList extends AbricosModelList {

    private $_userRoleList = array();

    private $_maxid = 0;

    public $isNewItem = false;

    /**
     * @param TeamUserRole $item
     */
    public function Add($item){
        if ($item->id === 0){
            $item->id = $this->_maxid + 1;

            $item->isNewItem = true;
            $this->isNewItem = true;
        }

        $this->_maxid = max($this->_maxid, $item->id);

        parent::Add($item);

        if (!isset($this->_userRoleList[$item->module])){
            $this->_userRoleList[$item->module] = array();
        }

        $this->_userRoleList[$item->module][$item->group] = $item;
    }

    /**
     * @param $module
     * @param $group
     * @return TeamUserRole
     */
    public function GetByPath($module, $group){
        return isset($this->_userRoleList[$module][$group]) ?
            $this->_userRoleList[$module][$group] : null;
    }
}

/**
 * Class TeamPlugin
 *
 * @property string $id App Module Name
 * @property string $title
 * @property bool $isCommunity
 */
class TeamPlugin extends AbricosModel {
    protected $_structModule = 'team';
    protected $_structName = 'Plugin';
}

/**
 * Class TeamPluginList
 *
 * @method TeamPlugin Get(string $name)
 * @method TeamPlugin GetByIndex(int $i)
 */
class TeamPluginList extends AbricosModelList {
}

/**
 * Interface TeamSaveVars
 *
 * @property int $teamid
 * @property string $module
 * @property string $title
 */
interface TeamSaveVars {
}

/**
 * Class TeamSave
 *
 * @property TeamSaveVars $vars
 *
 * @property int $teamid
 */
class TeamSave extends AbricosResponse {
    const CODE_OK = 1;
    const CODE_ERR_FIELDS = 2;
    const CODE_ERR_TITLE = 4;

    protected $_structModule = 'team';
    protected $_structName = 'TeamSave';
}

/**
 * Class Team
 *
 * @property int $id Team ID
 * @property string $module Owner module name
 * @property int $userid
 * @property string $title
 * @property string $logo
 * @property string $userActions Enable user action id (join by `,`)
 */
class Team extends AbricosModel {
    protected $_structModule = 'team';
    protected $_structName = 'Team';

    public $extends = array();

    public function ToJSON(){
        $json = parent::ToJSON();

        /**
         * @var string $key
         * @var AbricosModel $value
         */
        foreach ($this->extends as $key => $value){
            if (!isset($json->extends)){
                $json->extends = array();
            }
            $json->extends[$key] = $value->ToJSON();
        }

        return $json;
    }
}

/**
 * Class TeamList
 *
 * @method Team Get(int $id)
 * @method Team GetByIndex(int $i)
 */
class TeamList extends AbricosModelList {
}

/**
 * Interface TeamListFilterVars
 *
 * @property string $module Owner Module
 */
interface TeamListFilterVars {
}

/**
 * Class TeamListFilter
 *
 * @property TeamListFilterVars $vars
 * @property TeamList $items
 */
class TeamListFilter extends AbricosResponse {
    protected $_structModule = 'team';
    protected $_structName = 'TeamListFilter';
}

/**
 * Class TeamMember
 *
 * @property int $id Member ID (User ID)
 * @property string $module
 * @property int $teamid
 */
class TeamMember extends AbricosModel {

    protected $_structModule = 'team';
    protected $_structName = 'Member';

    public $extends = array();

    public function ToJSON(){
        $json = parent::ToJSON();

        /**
         * @var string $key
         * @var AbricosModel $value
         */
        foreach ($this->extends as $key => $value){
            if (!isset($json->extends)){
                $json->extends = array();
            }
            $json->extends[$key] = $value->ToJSON();
        }

        return $json;
    }
}

/**
 * Class TeamMemberList
 *
 * @method TeamMember Get($id)
 * @method TeamMember GetByIndex($i)
 */
class TeamMemberList extends AbricosModelList {
}

/**
 * Interface TeamMemberListFilterVars
 *
 * @property string $policy
 * @property int $teamid
 */
interface TeamMemberListFilterVars {
}

/**
 * Class TeamMemberListFilter
 *
 * @property TeamMemberListFilterVars $vars
 * @property TeamMemberList $items
 */
class TeamMemberListFilter extends AbricosResponse {
    const CODE_OK = 1;
    const CODE_BAD_METHOD = 2;

    const METHOD_TEAM = 'team';
    const METHOD_IINTEAMS = 'iInTeams';
    const METHOD_MEMBER = 'member';

    protected $_structModule = 'team';
    protected $_structName = 'MemberListFilter';
}

/**
 * Class TeamMemberSaveVars
 *
 * @property int $memberid
 * @property int $teamid
 * @property string $policy
 * @property object $invite
 * @property string $firstName
 * @property string $lastName
 */
class TeamMemberSaveVars {
}

/**
 * Class TeamMemberSave
 *
 * @property TeamMemberSaveVars $vars
 * @property int $teamid
 * @property int $memberid
 * @property string $policy
 */
class TeamMemberSave extends AbricosResponse {
    protected $_structModule = 'team';
    protected $_structName = 'MemberSave';
}