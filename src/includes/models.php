<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

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
 * @property string $module Owner module name
 * @property int $userid
 * @property string $title
 * @property string $email
 * @property string $descript
 * @property string $site
 * @property string $logo
 * @property int $memberCount
 * @property bool $isAnyJoin
 * @property bool $isAwaitModer
 * @property TeamMember $member
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
 * @property int $teamid
 * @property int $userid
 * @property string $status
 * @property string $role
 * @property bool $isPrivate
 */
class TeamMember extends AbricosModel {

    const STATUS_WAITING = 'waiting';
    const STATUS_JOINED = 'joined';
    const STATUS_REMOVED = 'removed';

    const ROLE_USER = 'user';
    const ROLE_EDITOR = 'editor';
    const ROLE_MODERATOR = 'moderator';
    const ROLE_ADMIN = 'admin';

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
 * @property string $method Must be: team|inTeams
 * @property int $teamid
 * @property array $teamids
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

    protected $_structModule = 'team';
    protected $_structName = 'MemberListFilter';
}

/**
 * Class TeamMemberSaveVars
 *
 * @property int $memberid
 * @property int $teamid
 * @property object $invite
 * @property string $firstName
 * @property string $lastName
 */
class TeamMemberSaveVars {
}

/**
 * Interface TeamMemberSaveCodes
 *
 * @property int $OK
 */
interface TeamMemberSaveCodes {
}

/**
 * Class TeamMemberSave
 *
 * @property TeamMemberSaveVars $vars
 * @property TeamMemberSaveCodes $codes
 */
class TeamMemberSave extends AbricosResponse {
    protected $_structModule = 'team';
    protected $_structName = 'MemberSave';
}