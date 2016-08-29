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
			
			title varchar(50) NOT NULL DEFAULT '' COMMENT 'Название общества',
			email varchar(50) NOT NULL DEFAULT '' COMMENT '',
			descript TEXT NOT NULL  COMMENT 'Описание',
			site varchar(50) NOT NULL DEFAULT '' COMMENT '',
			logo varchar(8) NOT NULL DEFAULT '' COMMENT '',

			memberCount int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Количество участников',
			
			isPrivate tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Группа видна только участникам',
			
			isAnyJoin tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1 - любой может вступить в группу',

			isAwaitModer tinyint(1) UNSIGNED NOT NULL default '0' COMMENT '1-ожидает модерацию',
			
			dateline int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			deldate int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата удаления',
			upddate int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата обновления',

			PRIMARY KEY (teamid),
			KEY team (isAwaitModer, ownerModule, deldate),
			KEY (userid)
		)".$charset
    );

    /*
     * Отношение пользователя к группе
     * определяется флагом isMember (0 - гость группы, 1 - участник группы)
     *
     * статус приглашения пользователя isInvite:
     * 0 - нет статуса,
     * 1 - пользователь приглашен в группу одним из ее участников (система ждет его подтверждения),
     * 2 - пользователь подтвердил это приглашение (теперь он член группы),
     * 3 - пользователь отказался от приглашения
     *
     * флаг удаление из группы isRemove:
     * 0 - не удален,
     * 1 - удален админом,
     * 2 - удален самим участником
     */
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."team_member (
			memberid int(10) UNSIGNED NOT NULL auto_increment,
			teamid int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Сообщество',
			userid int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Пользователь',

			isMember tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Член общества: 0 - нет, 1 - да',
			isAdmin tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Админ общества',

			isInvite tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1 - приглашен админом группы',
			isJoinRequest tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1 - сам сделал запрос на вступление',

			isRemove tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Флаг удаления из группы',
			
			relUserId int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Автор приглашения или подтверждения вступления в группу',
			isPrivate tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Скрывать свое участие не членам группы',
			
			dateline int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			upddate int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
			
			PRIMARY KEY (memberid),
			UNIQUE KEY member (teamid, userid),
			KEY isMember (isMember),
			KEY isRemove (isRemove),
			KEY invite (isInvite, isJoinRequest)
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
