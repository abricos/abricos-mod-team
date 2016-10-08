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
            teamid INT(10) UNSIGNED NOT NULL auto_increment COMMENT 'Идентификатор сообщества',
            ownerModule VARCHAR(25) NOT NULL DEFAULT '' COMMENT 'Модуль основатель',
            
            userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Основатель',
            
            title VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Название общества',
            logo VARCHAR(8) NOT NULL DEFAULT '' COMMENT '',
            
            memberCount INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Количество участников',
            
            dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата создания',
            upddate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
            deldate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата удаления',
            
            PRIMARY KEY (teamid),
            KEY ownerModule (ownerModule),
            KEY deldate (deldate)
		)".$charset
    );

    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."team_policy (
            policyid INT(10) UNSIGNED NOT NULL auto_increment,
            teamid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
            
            policyName VARCHAR(25) NOT NULL DEFAULT '' COMMENT '',
            descript TEXT NOT NULL COMMENT '',
            
            isSys tinyINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
            isPlugin tinyINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
        
            PRIMARY KEY (policyid),
            UNIQUE KEY policy (teamid, policyName),
            KEY teamid (teamid)
        )".$charset
    );

    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."team_action (
            actionid INT(10) UNSIGNED NOT NULL auto_increment,
            
            ownerModule VARCHAR(25) NOT NULL DEFAULT '' COMMENT '',
            actionGroup VARCHAR(25) NOT NULL DEFAULT '' COMMENT '',
            actionName VARCHAR(25) NOT NULL DEFAULT '' COMMENT '',
            
            code INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
        
            PRIMARY KEY (actionid),
            UNIQUE KEY pAction (ownerModule, actionGroup, actionName)
        )".$charset
    );

    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."team_role (
            roleid INT(10) UNSIGNED NOT NULL auto_increment,
            
            policyid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
            ownerModule VARCHAR(25) NOT NULL DEFAULT '' COMMENT '',
            actionGroup VARCHAR(25) NOT NULL DEFAULT '' COMMENT '',
            mask INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',

            upddate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',

            PRIMARY KEY (roleid),
            UNIQUE KEY role (policyid, ownerModule, actionGroup)
        )".$charset
    );

    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."team_userPolicy (
            userpolid INT(10) UNSIGNED NOT NULL auto_increment,
            
            teamid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
            userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
            policyid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',

            authorid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',

            PRIMARY KEY (userpolid),
            KEY teamid (teamid),
            UNIQUE KEY userpolid (userid, policyid)
        )".$charset
    );

    // кэш ролей пользователя в сообществе (чистить при любых изменениях ролей!)
    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."team_userRole (
            userroleid INT(10) UNSIGNED NOT NULL auto_increment,
            
            teamid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
            userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
            ownerModule VARCHAR(25) NOT NULL DEFAULT '' COMMENT '',
            actionGroup VARCHAR(25) NOT NULL DEFAULT '' COMMENT '',
            mask INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',

            PRIMARY KEY (userroleid),
            KEY userTeam (teamid, userid),
            KEY ownerModule (ownerModule),
            UNIQUE KEY userrole (teamid, userid, ownerModule, actionGroup)
        )".$charset
    );

    // кэш разрешенных действий пользователя в сообществе (чистить при любых изменениях ролей!)
    // используется только при получении сообщества пользователем
    $db->query_write("
        CREATE TABLE IF NOT EXISTS ".$pfx."team_userActions (
            useractionid INT(10) UNSIGNED NOT NULL auto_increment,
            
            teamid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
            userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
            actions VARCHAR(255) NOT NULL DEFAULT '' COMMENT '',

            PRIMARY KEY (useractionid),
            UNIQUE KEY userrole (teamid, userid)
        )".$charset
    );

}

// При последующем обновлении обязательно зачисти весь кэш ролей пользователей
// чтобы инициировать процедуру проверку основных ролей
// $db->query_write("TRUNCATE TABLE ".$pfx."team_userRole");
