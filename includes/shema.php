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
			`teamtype` varchar(25) NOT NULL DEFAULT '' COMMENT 'Тип сообщества - содержит имя модуля (обработчик типа)',
			
			`userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Основатель',
			
			`title` varchar(50) NOT NULL DEFAULT '' COMMENT 'Название общества',
			`email` varchar(50) NOT NULL DEFAULT '' COMMENT '',
			`descript` TEXT NOT NULL  COMMENT 'Описание',
			`site` varchar(50) NOT NULL DEFAULT '' COMMENT '',
			`logo` varchar(8) NOT NULL DEFAULT '' COMMENT '',

			`membercount` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Количество участников',
			
			`isanyjoin` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1 - любой может вступить в группу',

			`ismoder` tinyint(1) UNSIGNED NOT NULL default '0' COMMENT '1-ожидает модерацию',
			
			`dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			`deldate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата удаления',
			`upddate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата обновления',

			PRIMARY KEY  (`teamid`),
			KEY `team` (`ismoder`, `module`, `deldate`),
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
			
			UNIQUE KEY `userrole` (`teamid`,`userid`),
			KEY `ismember` (`ismember`),
			KEY `isremove` (`isremove`),
			KEY `invite` (`isjoinrequest`, `isinvite`)
		)".$charset
	);
	
}
if ($updateManager->isUpdate('0.1.2')){
	
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."team_membergroup (
			`groupid` int(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор группы',
			`parentgroupid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Родитель',
				
			`teamid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Сообщество',
			`module` varchar(25) NOT NULL DEFAULT '' COMMENT 'Модуль создатель',
			
			`title` varchar(50) NOT NULL DEFAULT '' COMMENT 'Название',
			`descript` TEXT NOT NULL  COMMENT 'Описание',
				
			`dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			`deldate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата удаления',
			`upddate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
	
			PRIMARY KEY  (`groupid`),
			KEY (`teamid`, `module`, `deldate`)
		)".$charset
	);
	
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."team_memberingroup (
			`groupid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Группа',
			`userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Сообщество',
	
			`dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата создания',
	
			UNIQUE KEY `memberingroup` (`groupid`, `userid`)
		)".$charset
	);

	// файлы
	$db->query_write("
		CREATE TABLE IF NOT EXISTS `".$pfx."team_filebuffer` (
			`fileid` int(10) UNSIGNED NOT NULL auto_increment,
			`userid` int(10) UNSIGNED NOT NULL COMMENT 'Пользователь',
			`filehash` varchar(8) NOT NULL COMMENT 'Идентификатор файла',
			`filename` varchar(250) NOT NULL COMMENT 'Имя файла',
			`ord` int(4) UNSIGNED NOT NULL default '0' COMMENT 'Сортировка',
			`dateline` int(10) UNSIGNED NOT NULL default '0' COMMENT 'Дата добавления',
			PRIMARY KEY (`fileid`),
			KEY `dateline` (`dateline`)
		)". $charset
	);
}
if ($updateManager->isUpdate('0.1.2') && !$updateManager->isInstall()){

	$db->query_write("
		ALTER TABLE `".$pfx."team`
		ADD `teamtype` varchar(25) NOT NULL DEFAULT '' COMMENT 'Тип сообщества - содержит имя модуля (обработчик типа)',
		ADD `ismoder` tinyint(1) UNSIGNED NOT NULL default '0' COMMENT '1-ожидает модерацию',
		DROP INDEX `team`,
		ADD INDEX `team` (`ismoder`, `module`, `deldate`)
	");
	
}
?>