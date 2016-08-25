<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'classesappman.php';

class TeamAppNavigator {

    /**
     * @var TeamAppManager
     */
    public $manager;
    public $isAbs;

    public function __construct($manager, $isAbs = false){
        $this->manager = $manager;
        $this->isAbs = $isAbs;
    }

    public function URL(Team $team){
        $man = $this->manager;
        $url = $this->TeamView($team);
        return $url.$man->moduleName."/".$man->name."/";
    }

    public function TeamView(Team $team){
        return $team->Manager()->Navigator($this->isAbs)->TeamView($team->id);
    }
}

class TeamAppInitData {

    /**
     * @var TeamAppManager
     */
    public $manager;

    public function __construct($manager){
        $this->manager = $manager;
    }

    public function ToAJAX(){
        $ret = new stdClass();

        return $ret;
    }
}
