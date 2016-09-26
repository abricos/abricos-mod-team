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

    public static function TeamUserRole(Ab_Database $db, $teamid){
        $sql = "
			SELECT
                m.memberid,
                t.teamid,
			    t.ownerModule as module,
                m.status,
                m.role,
                m.isPrivate as isPrivate
            FROM ".$db->prefix."team t
            LEFT JOIN ".$db->prefix."team_member m ON m.teamid=t.teamid
            WHERE m.userid=".intval(Abricos::$user->id)."
                AND t.deldate=0
		";
        if (is_array($teamid)){
            $count = count($teamid);
            if ($count === 0){
                return;
            }
            $wha = array();
            for ($i = 0; $i < $count; $i++){
                $wha[] = "t.teamid=".intval($teamid[$i])."";
            }
            $sql .= "
                AND (".implode(" OR ", $wha).")
            ";
            return $db->query_read($sql);
        } else {
            $sql .= "
                AND t.teamid=".intval($teamid)."
                LIMIT 1
            ";
            return $db->query_first($sql);
        }
    }

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

    public static function TeamUpdate(Ab_Database $db, TeamSave $r){
        $sql = "
			UPDATE ".$db->prefix."team
			SET title='".bkstr($r->vars->title)."', 
			    upddate=".TIMENOW."
			WHERE teamid=".intval($r->vars->teamid)."
		";
        $db->query_write($sql);
    }

    public static function TeamList(Ab_Database $db, TeamListFilter $r){
        $sql = "
			SELECT t.*
            FROM ".$db->prefix."team t
            WHERE t.ownerModule='".bkstr($r->vars->module)."'
                AND t.deldate=0
		";
        return $db->query_read($sql);
    }

    public static function Team(Ab_Database $db, $teamid){
        $sql = "
			SELECT t.*
            FROM ".$db->prefix."team t
            WHERE t.teamid=".intval($teamid)."
                AND t.deldate=0
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

    public static function MemberInviteNewUser(Ab_Database $db, TeamMemberSave $rSave){
        $sql = "
			INSERT INTO ".$db->prefix."team_member
				(teamid, userid, status, role, dateline) VALUES (
				".bkint($rSave->vars->teamid).",
				".bkint($rSave->userid).",
				'waiting',
				'user',
				".TIMENOW."
			)
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    public static function MemberUpdate(){

    }

    public static function MemberListMyByTeams(Ab_Database $db, $teamids){
        $sql = "
			SELECT *
            FROM ".$db->prefix."team_member
            WHERE 
		";
        return $db->query_read($sql);
    }

    public static function Member(Ab_Database $db, $teamid, $memberid){
        $sql = "
			SELECT m.*, t.ownerModule as module
            FROM ".$db->prefix."team_member m
            INNER JOIN ".$db->prefix."team t ON m.teamid=t.teamid
            WHERE m.teamid=".intval($teamid)."
                AND m.memberid=".intval($memberid)."
                AND t.deldate=0
		";
        return $db->query_first($sql);
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
			SELECT m.*, t.ownerModule as module
            FROM ".$db->prefix."team_member m
            INNER JOIN ".$db->prefix."team t ON m.teamid=t.teamid
            WHERE ".$where."
                AND t.deldate=0
		";
        return $db->query_read($sql);
    }

}
