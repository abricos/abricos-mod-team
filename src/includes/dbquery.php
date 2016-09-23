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

    public static function TeamList(Ab_Database $db, TeamListFilter $r){
        $sql = "
			SELECT *
            FROM ".$db->prefix."team
            WHERE ownerModule='".bkstr($r->vars->module)."'
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

    public static function MemberListMyByTeams(Ab_Database $db, $teamids){

        $sql = "
			SELECT *
            FROM ".$db->prefix."team_member
            WHERE 
		";
        return $db->query_read($sql);
    }

    public static function MemberList(Ab_Database $db, TeamMemberListFilter $filter){
        $vars = $filter->vars;

        if ($vars->method === 'team'){
            $where = "m.teamid=".intval($vars->teamid)."";
        } else if ($vars->method === 'iInTeams'){
            $count = count($vars->teamids);
            if ($count === 0){
                return;
            }
            $wha = array();
            for ($i = 0; $i < $count; $i++){
                $wha[] = "m.teamid=".intval($vars->teamids[$i]);
            }
            $where = "m.userid=".intval(Abricos::$user->id)." 
                AND (".implode(" OR ", $wha).")";
        } else {
            return null;
        }

        $sql = "
			SELECT m.*
            FROM ".$db->prefix."team_member m
            INNER JOIN ".$db->prefix."team t ON m.teamid=t.teamid
            WHERE ".$where."
		";
        return $db->query_read($sql);
    }

}
