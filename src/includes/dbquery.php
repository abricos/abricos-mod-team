<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class TeamQuery
 */
class TeamQuery {

    public static function TeamAppend(Ab_Database $db, $ownerModule, $d){
        $sql = "
			INSERT INTO ".$db->prefix."team
				(module, userid, title, dateline, upddate) VALUES (
				'".bkstr($ownerModule)."',
				".bkint(Abricos::$user->id).",
				'".bkstr($d->title)."',
				".TIMENOW.",
				".TIMENOW."
			)
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    public static function TeamList(Ab_Database $db, $ownerModule){
        $sql = "
			SELECT *
            FROM ".$db->prefix."team
            WHERE module='".bkstr($ownerModule)."'
		";
        return $db->query_read($sql);
    }
}
