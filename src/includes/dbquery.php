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

    public static function TeamAppend(Ab_Database $db, TeamSave $r){
        $sql = "
			INSERT INTO ".$db->prefix."team
				(ownerModule, userid, title, dateline, upddate) VALUES (
				'".bkstr($r->vars->module)."',
				".bkint(Abricos::$user->id).",
				'".bkstr($r->vars->title)."',
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
            WHERE ownerModule='".bkstr($ownerModule)."'
		";
        return $db->query_read($sql);
    }

    public static function Team(Ab_Database $db, $teamid){
        $sql = "
			SELECT *
            FROM ".$db->prefix."team
            WHERE teamid=".intval($teamid)."
            LIMIT 1
		";
        return $db->query_first($sql);
    }

    /* * * * * * * * * * * * * * Member * * * * * * * * * * * * */

    public static function MemberAppendByNewTeam(Ab_Database $db, TeamSave $r){
        $sql = "
			INSERT INTO ".$db->prefix."team_member
				(teamid, userid, status, role, dateline) VALUES (
				".bkint($r->teamid).",
				".bkint(Abricos::$user->id).",
				'joined',
				'admin',
				".TIMENOW."
			)
		";
        $db->query_write($sql);
        return $db->insert_id();

    }

    public static function MemberAppend(Ab_Database $db, TeamMember $member){
        $sql = "
			INSERT INTO ".$db->prefix."team_member
				(teamid, userid, relUserid, isMember, isAdmin, 
				isInvite, isJoinRequest, isPrivate, dateline) VALUES (
				".bkint($member->teamid).",
				".bkint($member->userid).",
				".bkint($member->relUserId).",
				".bkint($member->isMember).",
				".bkint($member->isAdmin).",
				".bkint($member->isInvite).",
				".bkint($member->isJoinRequest).",
				".bkint($member->isPrivate).",
				".TIMENOW."
			)
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    public static function MemberList(Ab_Database $db, $teamid){
        $sql = "
			SELECT *
            FROM ".$db->prefix."team_member
            WHERE teamid=".intval($teamid)."
		";
        return $db->query_read($sql);
    }

}
