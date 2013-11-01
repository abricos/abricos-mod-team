<?php 
/**
 * @package Abricos
 * @subpackage Team
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class TeamAppManager {
	
	/**
	 * @var Ab_ModuleManager
	 */
	public $modManager = null;

	/**
	 * Имя управляющего модуля
	 * @var string
	 */
	public $moduleName = '';
	
	/**
	 * Имя приложения
	 * @var string
	 */
	public $name = '';

	/**
	 * @var Ab_Database
	 */
	public $db;
	
	/**
	 * @var User
	 */
	public $user;
	
	/**
	 * @var integer
	 */
	public $userid;
	
	
	public $TeamAppNavigatorClass = TeamAppNavigator;
	
	/**
	 * @param Ab_ModuleManager $modManager
	 * @param string $appName
	 */
	public function __construct(Ab_ModuleManager $mman, $appName = ''){
		$this->modManager = $mman;
		$this->moduleName = $mman->module->name;
		$this->name = $appName;
		$this->db = $mman->db;
		$this->user = $mman->user;
		$this->userid = $mman->userid;
	}
	
	public function IsAdminRole(){ return false; }
	public function IsWriteRole(){ return false; }
	public function IsViewRole(){ return false; }
	
	
	private $_navigatorURI;
	private $_navigatorURL;
	
	/**
	 * @return TeamMemberNavigator
	 */
	public function Navigator($isURL = false){
		if ($isURL){
			if (empty($this->_navigatorURL)){
				$this->_navigatorURL = new $this->TeamAppNavigatorClass($this, true);
			}
			return $this->_navigatorURL;
		}
		if (empty($this->_navigatorURI)){
			$this->_navigatorURI = new $this->TeamAppNavigatorClass($this, false);
		}
		return $this->_navigatorURI;
	}
	
	public final function AJAX($d){
		$ret = new stdClass();
		$aDo = explode("|", $d->do);

		$aResult = array();
		for ($i=0;$i<count($aDo);$i++){
			$sDo = trim($aDo[$i]);
			if (empty($sDo)){ continue; }
			
			$d->do = $sDo;
			array_push($aResult,  $this->AJAXMethod($d));
		}
		
		$ret->result = null;
		for ($i=0;$i<count($aResult);$i++){
			$result = $aResult[$i];
			if (!is_object($result)){ continue; }
			
			if (!is_object($ret->result)){
				$ret->result = $result;
				continue;
			}
			
			$vars = get_object_vars($result);
			foreach ($vars as $var => $obj){
				$ret->result->$var = $obj;
			}
		}
	
		return $ret;
	}
	
	public function AJAXMethod($d){
		switch($d->do){
			case 'appdata': return $this->TeamExtendedDataToAJAX($d->teamid);
		}
		return null;
	}
	
	private $_cache = array();
	
	public function CacheClear($cacheName = '', $id = 0){
		if (empty($cacheName)){
			$this->_cache = array();
		}else{
			if ($id == 0){
				$this->_cache[$cacheName] = array();
			}else{
				$this->_cache[$cacheName]["id".$id] = null;
			}
		}
	}
	
	protected  function CacheAdd($cacheName, $id, $object){
		if (!is_array($this->_cache[$cacheName])){
			$this->_cache[$cacheName] = array();
		}
		$this->_cache[$cacheName]["id".$id] = $object;
	}
	
	public function Cache($cacheName, $id){
			if (!is_array($this->_cache[$cacheName])){
			$this->_cache[$cacheName] = array();
		}
		return $this->_cache[$cacheName]["id".$id];
	}
	
	/**
	 * @param integer $teamid
	 * @return Team
	 */
	public function Team($teamid){
		Abricos::GetModule('team')->GetManager();
		return TeamModuleManager::$instance->Team($teamid);
	}

}


?>