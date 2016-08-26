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

class TeamUserRole extends AbricosModel {
    protected $_structModule = 'team';
    protected $_structName = 'UserRole';
}

class TeamUserRoleList extends AbricosModelList {
}
