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

    const CONFIG_ROLE = 'config.role';

    const ADMIN_VIEW = 'admin.view';
    const ADMIN_APPEND = 'admin.append';
    const ADMIN_UPDATE = 'admin.update';
    const ADMIN_REMOVE = 'admin.remove';

    public $group;
    public $name;

    public function __construct($action){
        $a = explode('.', $action);
        $this->group = $a[0];
        $this->name = $a[1];
    }
}

/**
 * Class TeamPolicy
 */
class TeamPolicy {
    const ADMIN = 'admin';

    /**
     * Приглашенные на вступлени в партию
     */
    const INVITE = 'invite';

    /**
     * Запросившие вступление в партию
     */
    const REQUEST = 'request';

    /**
     * Желающие скрыть свою принадлежность к сообществу для гостей
     */
    const HIDDEN = 'hidden';

    const GUEST = 'guest';
    const BANNED = 'banned';



    public static function GetDefaultPolicies(){
        return array(
            TeamPolicy::ADMIN => array(
                TeamAction::TEAM_VIEW,
                TeamAction::TEAM_UPDATE,

                TeamAction::CONFIG_ROLE,

                TeamAction::ADMIN_VIEW,
                TeamAction::ADMIN_APPEND,
                TeamAction::ADMIN_UPDATE,
                TeamAction::ADMIN_REMOVE,
            ),
            TeamPolicy::INVITE => array(
                TeamAction::TEAM_VIEW,
            ),
            TeamPolicy::REQUEST => array(
                TeamAction::TEAM_VIEW,
            ),
            TeamPolicy::HIDDEN => array(
                TeamAction::TEAM_VIEW,
            ),
            TeamPolicy::GUEST => array(
                TeamAction::TEAM_VIEW,
            ),
        );
    }
}

