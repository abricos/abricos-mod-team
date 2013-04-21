<?php
/**
 * Схема таблиц данного модуля.
 * 
 * @package Abricos
 * @subpackage Team
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author  Alexander Kuzmin <roosit@abricos.org>
 */

$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
$updateManager = Ab_UpdateManager::$current; 
$db = Abricos::$db;
$pfx = $db->prefix;

if ($updateManager->isInstall()){
	Abricos::GetModule('team')->permission->Install();

	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."team (
			`teamid` int(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор сообщества',
			`module` varchar(25) NOT NULL DEFAULT '' COMMENT 'Модуль создатель',
			
			`userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Основатель',
			
			`title` varchar(50) NOT NULL DEFAULT '' COMMENT 'Название общества',
			`email` varchar(50) NOT NULL DEFAULT '' COMMENT '',
			`descript` TEXT NOT NULL  COMMENT 'Описание',
			`site` varchar(50) NOT NULL DEFAULT '' COMMENT '',
			`logo` varchar(8) NOT NULL DEFAULT '' COMMENT '',

			`membercount` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Количество участников',
			
			`isanyjoin` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1 - любой может вступить в группу',
			
			`dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			`deldate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата удаления',
			`upddate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
			
			PRIMARY KEY  (`teamid`),
			KEY (`module`),
			KEY (`userid`)
		)".$charset
	);
	
	/*
	 * Отношение пользователя к группе 
	 * определяется флагом ismember (0 - гость группы, 1 - участник группы)
	 * 
	 * статус приглашения пользователя isinvite:
	 * 0 - нет статуса, 
	 * 1 - пользователь приглашен в группу одним из ее участников (система ждет его подтверждения),
	 * 2 - пользователь подтвердил это приглашение (теперь он член группы),
	 * 3 - пользователь отказался от приглашения
	 * 
	 * флаг удаление из группы isremove:
	 * 0 - не удален,
	 * 1 - удален админом,
	 * 2 - удален самим участником
	 *  
	 */
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."team_userrole (
			`teamid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Сообщество',
			`userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь',

			`ismember` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Член общества: 0 - нет, 1 - да',
			`isadmin` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Админ общества',

			`isinvite` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1 - приглашен админом группы',
			`isjoinrequest` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1 - сам сделал запрос на вступление',

			`isremove` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Флаг удаления из группы',
			
			`reluserid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Автор приглашения или подтверждения вступления в группу',
			`isprivate` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Скрывать свое участие не членам группы',
			
			`dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			`lastview` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата посещения группы',
			`upddate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
			
			UNIQUE KEY `userrole` (`teamid`,`userid`)
		)".$charset
	);
	
}

if ($updateManager->isUpdate('0.1.1')){
	$db->query_write("
		CREATE TABLE `".$pfx."team_event` (
			`eventid` integer(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор события',
				
			`teamid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Сообщество',
				
			`title` varchar(250) NOT NULL DEFAULT '' COMMENT 'Заголовок',
				
			`address` varchar(250) NOT NULL DEFAULT '' COMMENT 'Место проведения',
				
			`datefrom` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Начало события',
			`dateto` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Окончание события',
				
			`dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			`deldate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата удаления',
	
			PRIMARY KEY (`eventid`),
			KEY `deldate` (`deldate`)
		)". $charset
	);
	
}

?>