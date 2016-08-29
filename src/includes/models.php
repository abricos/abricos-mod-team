<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class Team
 *
 * @property string $module Owner module name
 * @property string $title
 * @property int $userid
 * @property int $memberCount
 * @property bool $isAnyJoin
 * @property bool $isAwaitModer
 * @property TeamMemberList $members
 */
class Team extends AbricosModel {
    protected $_structModule = 'team';
    protected $_structName = 'Team';
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
 * Class TeamMember
 *
 * @property int $teamid
 * @property int $userid
 * @property int $relUserId
 * @property bool $isMember
 * @property bool $isAdmin
 * @property bool $isInvite
 * @property bool $isJoinRequest
 * @property bool $isRemove
 * @property bool $isPrivate
 * @property int $date
 */
class TeamMember extends AbricosModel {
    protected $_structModule = 'team';
    protected $_structName = 'Member';
}

class TeamMemberList extends AbricosModelList {
}
