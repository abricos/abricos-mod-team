<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class TeamAction
 */
class TeamAction {
    const TEAM_VIEW = 'team.view';
    const TEAM_UPDATE = 'team.update';

    const ROLE_UPDATE = 'role.update';

    const MEMBER_APPEND = 'member.append';
    const MEMBER_UPDATE = 'member.update';
    const MEMBER_VIEW = 'member.view';
}

/**
 * Class TeamPolicy
 */
class TeamPolicy {
    const ADMIN = 'admin';
    const MEMBER = 'member';
    /**
     * Ожидающие вступления в участники
     */
    const WAITING = 'waiting';
    const GUEST = 'guest';
    const BANNED = 'banned';

    public static function GetDefaultPolicies(){
        return array(
            TeamPolicy::ADMIN => array(
                TeamAction::TEAM_VIEW,
                TeamAction::TEAM_UPDATE,

                TeamAction::ROLE_UPDATE,

                TeamAction::MEMBER_APPEND,
                TeamAction::MEMBER_UPDATE,
                TeamAction::MEMBER_VIEW,
            ),
            TeamPolicy::MEMBER => array(
                TeamAction::TEAM_VIEW,

                TeamAction::MEMBER_VIEW,
            ),
            TeamPolicy::WAITING => array(
                TeamAction::TEAM_VIEW,

                TeamAction::MEMBER_VIEW,
            ),
            TeamPolicy::GUEST => array(),
        );
    }
}

