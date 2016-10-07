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
                (urGuest.mask & a.code > 0)
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

    public static function MemberList(Ab_Database $db, TeamMemberListFilter $filter){
        $teamid = $filter->vars->teamid;
        $policyName = $filter->vars->policy;

        $sql = "
			SELECT up.userid
            FROM ".$db->prefix."team_userPolicy up
            INNER JOIN ".$db->prefix."team_policy p 
                ON up.policyid=p.policyid 
                    AND p.policyName='".bkstr($policyName)."'
            WHERE up.teamid=".intval($teamid)."
		";
        return $db->query_read($sql);
    }

    /* * * * * * * * * * * * * * Invite Policy * * * * * * * * * * * * */

    public static function UserInviteAppend(Ab_Database $db, $teamid, $userid, $policyid){
        $sql = "
			INSERT INTO ".$db->prefix."team_user_invite
            (teamid, userid, policyid, dateline) VALUES (
                ".intval($teamid).",
                ".intval($userid).",
                ".intval($policyid).",
                ".intval(TIMENOW)."
            )
		";
        $db->query_write($sql);
    }

}
