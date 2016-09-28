<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
$updateManager = Ab_UpdateManager::$current;
$db = Abricos::$db;
$pfx = $db->prefix;

if ($updateManager->isInstall()){
    Abricos::GetModule('team')->permission->Install();

    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."team (
			teamid int(10) UNSIGNED NOT NULL auto_increment COMMENT 'Идентификатор сообщества',
			ownerModule varchar(25) NOT NULL DEFAULT '' COMMENT 'Модуль основатель',
			
			userid int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Основатель',
			
			title varchar(255) NOT NULL DEFAULT '' COMMENT 'Название общества',
			logo varchar(8) NOT NULL DEFAULT '' COMMENT '',
			
			memberCount int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Количество участников',
			
			dateline int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			upddate int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
			deldate int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата удаления',

			PRIMARY KEY (teamid),
			KEY ownerModule (ownerModule),
			KEY deldate (deldate)
		)".$charset
    );

    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."team_member (
			memberid int(10) UNSIGNED NOT NULL auto_increment,
			teamid int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Сообщество',
			userid int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Пользователь',

			status ENUM('waiting', 'joined', 'removed') DEFAULT 'joined' COMMENT 'Статус',
			role ENUM('user', 'editor', 'moderator', 'admin') DEFAULT 'user' COMMENT 'Роль',

			isPrivate tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Скрывать свое участие не членам группы',
			
			dateline int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			upddate int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
			
			PRIMARY KEY (memberid),
			UNIQUE KEY member (teamid, userid),
			KEY teamid (teamid),
			KEY userid (userid),
			KEY status (status),
			KEY role (role)
		)".$charset
    );

    // файлы
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."team_filebuffer (
			fileid int(10) UNSIGNED NOT NULL auto_increment,
			userid int(10) UNSIGNED NOT NULL COMMENT 'Пользователь',
			filehash varchar(8) NOT NULL COMMENT 'Идентификатор файла',
			filename varchar(250) NOT NULL COMMENT 'Имя файла',
			ord int(4) UNSIGNED NOT NULL default '0' COMMENT 'Сортировка',
			dateline int(10) UNSIGNED NOT NULL default '0' COMMENT 'Дата добавления',
			PRIMARY KEY (fileid),
			KEY dateline (dateline)
		)".$charset
    );
}
