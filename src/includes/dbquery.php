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
				(ownerModule, userid, title, visibility, dateline, upddate) VALUES (
				'".bkstr($r->vars->module)."',
				".bkint(Abricos::$user->id).",
				'".bkstr($r->vars->title)."',
				'".bkstr($r->vars->visibility)."',
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

    public static function TeamList(TeamApp $app, TeamListFilter $r){
        $actView = new TeamAction(TeamAction::TEAM_VIEW);

        $db = $app->db;
        $sql = "
			SELECT t.*
            FROM ".$db->prefix."team t
            INNER JOIN ".$db->prefix."team_action a
                ON t.ownerModule=a.ownerModule
                    AND a.actionGroup='".$actView->group."'
                    AND actionName='".$actView->name."'
            INNER JOIN ".$db->prefix."team_userRole urGuest
                ON urGuest.teamid=t.teamid
                    AND urGuest.ownerModule=t.ownerModule
                    AND urGuest.actionGroup='".$actView->group."'
                    AND urGuest.userid=0
            LEFT JOIN ".$db->prefix."team_userRole urUser
                ON urUser.teamid=t.teamid
                    AND urUser.ownerModule=t.ownerModule
                    AND urUser.actionGroup='".$actView->group."'
                    AND urUser.userid=".intval(Abricos::$user->id)."
            WHERE t.deldate=0 AND (
                (t.visibility='public' AND urGuest.mask & a.code > 0)
                OR
                (urUser.userid > 0 AND urUser.mask & a.code > 0)
            )
		";
        if (!empty($r->vars->module)){
            $sql .= " AND t.ownerModule='".bkstr($r->vars->module)."'";
        }

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

    public static function TeamOwnerModule(Ab_Database $db, $teamid){
        $sql = "
			SELECT ownerModule
            FROM ".$db->prefix."team
            WHERE teamid=".intval($teamid)."
            LIMIT 1
		";

        $d = $db->query_first($sql);
        if (empty($d)){
            return '';
        }
        return $d['ownerModule'];
    }

    /* * * * * * * * * * * * * * Team Policy * * * * * * * * * * * * */

    public static function PolicyList(Ab_Database $db, $teamid){
        $sql = "
			SELECT p.*
            FROM ".$db->prefix."team_policy p
            WHERE p.teamid=".intval($teamid)." 
		";
        return $db->query_read($sql);
    }

    public static function PolicyAppendByList(Ab_Database $db, TeamPolicyList $list){
        $insa = array();
        $count = $list->Count();
        for ($i = 0; $i < $count; $i++){
            $item = $list->GetByIndex($i);
            if (!$item->isNewItem){
                continue;
            }
            $insa[] = "(
                ".intval($item->teamid).",
                '".bkstr($item->name)."',
                1
            )";
        }
        if (count($insa) === 0){
            return;
        }
        $sql = "
			INSERT INTO ".$db->prefix."team_policy
            (teamid, policyName, isSys) 
            VALUES ".implode(',', $insa)."
		";
        $db->query_write($sql);
    }

    public static function ActionList(Ab_Database $db){
        $sql = "
			SELECT *
            FROM ".$db->prefix."team_action
		";
        return $db->query_read($sql);
    }

    public static function ActionAppendByList(Ab_Database $db, TeamActionList $list){
        $insa = array();
        $count = $list->Count();
        for ($i = 0; $i < $count; $i++){
            $item = $list->GetByIndex($i);
            if (!$item->isNewItem){
                continue;
            }
            $insa[] = "(
                '".bkstr($item->module)."',
                '".bkstr($item->group)."',
                '".bkstr($item->name)."',
                ".intval($item->code)."
            )";
        }
        if (count($insa) === 0){
            return;
        }
        $sql = "
			INSERT INTO ".$db->prefix."team_action
            (ownerModule, actionGroup, actionName, code) 
            VALUES ".implode(',', $insa)."
		";
        $db->query_write($sql);
    }

    public static function RoleList(Ab_Database $db, $teamid){
        $sql = "
			SELECT 
                r.*,
                p.policyName
            FROM ".$db->prefix."team_policy p
            INNER JOIN ".$db->prefix."team_role r ON r.policyid=p.policyid
            WHERE p.teamid=".intval($teamid)."
		";
        return $db->query_read($sql);
    }

    public static function RoleAppendByList(Ab_Database $db, TeamRoleList $list){
        $insa = array();
        $count = $list->Count();
        for ($i = 0; $i < $count; $i++){
            $item = $list->GetByIndex($i);
            if (!$item->isNewItem){
                continue;
            }
            $insa[] = "(
                ".intval($item->policyid).",
                '".bkstr($item->module)."',
                '".bkstr($item->group)."',
                ".intval($item->mask).",
                ".intval(TIMENOW)."
            )";
        }
        if (count($insa) === 0){
            return;
        }
        $sql = "
			INSERT INTO ".$db->prefix."team_role
            (policyid, ownerModule, actionGroup, mask, upddate) 
            VALUES ".implode(',', $insa)."
		";
        $db->query_write($sql);
    }

    public static function RoleUpdate(Ab_Database $db, TeamRole $role){
        $sql = "
			UPDATE ".$db->prefix."team_role
			SET mask=".intval($role->mask).",
                upddate=".intval(TIMENOW)."
            WHERE roleid=".intval($role->id)."
		";
        $db->query_write($sql);
    }

    /* * * * * * * * * * * * * * User Policy * * * * * * * * * * * * */

    public static function UserPolicyAppend(Ab_Database $db, $userid, TeamPolicyItem $policy){
        $sql = "
			INSERT IGNORE INTO ".$db->prefix."team_userPolicy
				(teamid, userid, policyid, authorid, dateline) VALUES (
				".bkint($policy->teamid).",
				".bkint($userid).",
				".bkint($policy->id).",
				".bkint(Abricos::$user->id).",
				".TIMENOW."
			)
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    public static function UserPolicyList(Ab_Database $db, $teamid, $userid){
        $sql = "
			SELECT p.*
            FROM ".$db->prefix."team_userPolicy p
            WHERE p.teamid=".intval($teamid)."
                AND p.userid=".intval($userid)."
		";
        return $db->query_read($sql);
    }

    public static function UserRoleList(Ab_Database $db, $teamid, $userid){
        $sql = "
			SELECT *
            FROM ".$db->prefix."team_userRole
            WHERE teamid=".intval($teamid)."
                AND userid=".intval($userid)."
		";
        return $db->query_read($sql);
    }

    public static function UserRoleAppendByList(Ab_Database $db, TeamUserRoleList $list){
        $insa = array();
        $count = $list->Count();
        for ($i = 0; $i < $count; $i++){
            $item = $list->GetByIndex($i);
            if (!$item->isNewItem){
                continue;
            }
            $insa[] = "(
                ".intval($item->teamid).",
                ".intval($item->userid).",
                '".bkstr($item->module)."',
                '".bkstr($item->group)."',
                ".intval($item->mask)."
            )";
        }
        if (count($insa) === 0){
            return;
        }
        $sql = "
			INSERT INTO ".$db->prefix."team_userRole
            (teamid, userid, ownerModule, actionGroup, mask) 
            VALUES ".implode(',', $insa)."
		";
        $db->query_write($sql);
    }

    public static function UserRoleClean(Ab_Database $db, $teamid, $userid = -1){
        $sql = "
			DELETE FROM ".$db->prefix."team_userRole
            WHERE teamid=".intval($teamid)."
		";
        if ($userid > -1){
            $sql .= " AND userid=".intval($userid)." ";
        }
        return $db->query_write($sql);
    }

    /* * * * * * * * * * * * * * Member * * * * * * * * * * * * */

    public static function TeamMemberRole(Ab_Database $db, $teamid){
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

    public static function Member(Ab_Database $db, $teamid, $memberid){
        $sql = "
			SELECT 
			    m.*, 
			    t.ownerModule as module,
			    rm.status as myStatus,
			    rm.role as myRole
            FROM ".$db->prefix."team_member m
            INNER JOIN ".$db->prefix."team t ON m.teamid=t.teamid
            LEFT JOIN ".$db->prefix."team_member rm 
                ON t.teamid=rm.teamid AND rm.userid=".intval(Abricos::$user->id)."
            WHERE m.teamid=".intval($teamid)."
                AND m.memberid=".intval($memberid)."
                AND t.deldate=0
                AND (
                    m.userid=rm.userid
                    OR (m.status='joined')
                    OR (rm.status='joined' AND rm.role='admin')
                )
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
			SELECT 
			    m.*, 
			    t.ownerModule as module,
			    rm.status as myStatus,
			    rm.role as myRole
            FROM ".$db->prefix."team_member m
            INNER JOIN ".$db->prefix."team t ON m.teamid=t.teamid
            LEFT JOIN ".$db->prefix."team_member rm 
                ON t.teamid=rm.teamid AND rm.userid=".intval(Abricos::$user->id)."
            WHERE ".$where." AND t.deldate=0
                AND (
                    (m.userid=rm.userid)
                    OR (m.status='joined')
                    OR (rm.status='joined' AND rm.role='admin')
                )
		";
        return $db->query_read($sql);
    }
}
