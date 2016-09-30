<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class TeamUserAction
 */
class TeamUserAction {
    const TEAM_EDIT = 1;
    const MEMBER_APPEND = 2;
}

class TeamUserGroup {
    public $name;

    public function __construct($name){
    }
}