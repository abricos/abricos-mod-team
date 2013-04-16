<?php
/**
 * @package Abricos
 * @subpackage Team
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class TeamQuery {
	
	/**
	 * Список групп с правами текущего пользователя на эти группы
	 *
	 * @param Ab_Database $db
	 * @param string $module имя модуля
	 */
	public static function TeamList(Ab_Database $db, $module, $page = 1, $memberid = 0, $teamid = 0){
		$memberid = intval($memberid);
		$teamid = intval($teamid);
		$curUserid = Abricos::$user->id;
		$sql = "
			SELECT
				t.teamid as id,
				t.module as m,
				t.userid as auid,
				t.title as tl,
				t.email as eml,
				t.descript as dsc,
				t.site,
				t.logo,
				t.isanyjoin as anj,
				t.membercount as mcnt
				
				".($curUserid==0 ? "" : ",
					ur.ismember,
					ur.isadmin,
					ur.isjoinrequest,
					ur.isinvite,
					ur.reluserid,
					ur.isremove
				")."
				
				
			FROM ".$db->prefix."team t
			
			".($curUserid==0 ? "" : "
			
			LEFT JOIN ".$db->prefix."team_userrole ur ON t.teamid=ur.teamid
				AND ur.userid=".bkint(Abricos::$user->id)."

			")."			

			".($memberid==0 ? "" : "
			
			LEFT JOIN ".$db->prefix."team_userrole urm ON t.teamid=urm.teamid
				AND urm.userid=".bkint($memberid)."

			")."
			
			WHERE t.deldate=0 AND t.module='".bkstr($module)."'

			".($memberid==0 ? "" : "
				AND urm.ismember=1
			")."
			
			".($teamid==0 ? "" : "
				AND t.teamid=".bkint($teamid)."
			")."
			
			".($teamid==0 ? "" : "
				LIMIT 1
			")."
		";
		return $db->query_read($sql);
	}
	
	/**
	 * Группа с правами текущего пользователя на неё
	 *
	 * @param Ab_Database $db
	 * @param integer $teamid
	 * @param integer $userid текущий пользователь для выявление его ролей к этой группе
	 */
	public static function Team(Ab_Database $db, $module, $teamid){
		$rows = TeamQuery::TeamList($db, $module, 1, 0, $teamid);
		while (($row = $db->fetch_array($rows))){
			return $row;
		}
		return null;
	}
	
	
	
	public static function TeamMemberCountRecalc(Ab_Database $db, $teamid){
		$sql = "
			SELECT count(ur.teamid) as cnt
			FROM ".$db->prefix."team_userrole ur
			WHERE ur.teamid=".bkint($teamid)." AND ur.ismember=1
			GROUP BY ur.teamid
		";
		$row = $db->query_first($sql);
		$sql = "
			UPDATE ".$db->prefix."team
			SET membercount=".intval($row['cnt'])."
			WHERE teamid=".bkint($teamid)." 
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function TeamAppend(Ab_Database $db, $module, $userid, $d){
		$sql = "
			INSERT INTO ".$db->prefix."team
				(module, userid, title, email, descript, site, logo, isanyjoin, dateline, upddate) VALUES (
				'".bkstr($module)."',
				".bkint($userid).",
				'".bkstr($d->tl)."',
				'".bkstr($d->eml)."',
				'".bkstr($d->dsc)."',
				'".bkstr($d->site)."',
				'".bkstr($d->logo)."',
				".bkint($d->anj).",
				".TIMENOW.",
				".TIMENOW."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function TeamUpdate(Ab_Database $db, $d){
		$sql = "
			UPDATE ".$db->prefix."team
			SET
				title='".bkstr($d->tl)."',
				email='".bkstr($d->eml)."',
				descript='".bkstr($d->dsc)."',
				site='".bkstr($d->site)."',
				logo='".bkstr($d->logo)."',
				upddate=".TIMENOW."
			WHERE teamid=".bkint($d->id)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function TeamRemove(Ab_Database $db, $teamid){
		$sql = "
			UPDATE ".$db->prefix."team
			SET deldate=".TIMENOW."
			WHERE teamid=".bkint($teamid)."
			LIMIT 1
		";
		$db->query_write($sql);		
	}
	
	public static function UserRoleUpdate(Ab_Database $db, $teamid, $userid, $ismemeber, $isadmin){
		$sql = "
			INSERT INTO ".$db->prefix."team_userrole
			(teamid, userid, ismember, isadmin, dateline, upddate) VALUES (
				".bkint($teamid).",
				".bkint($userid).",
				".bkint($ismemeber).",
				".bkint($isadmin).",
				".TIMENOW.",
				".TIMENOW."
			) ON DUPLICATE KEY UPDATE
				ismember=".bkint($ismemeber).",
				isadmin=".bkint($isadmin).",
				upddate=".TIMENOW."
		";
		$db->query_write($sql);
	}
	
	
	/**
	 * Список участников группы
	 * 
	 * @param Ab_Database $db
	 * @param integer $teamid
	 * @param boolean $isAdmin
	 */
	public static function MemberList(Ab_Database $db, $teamid, $isAdmin = false, $memberid = 0){
		$arr = array();
		
		// этот пользователь в этом списке
		array_push($arr, "
			SELECT
				userid,
				ismember,
				isadmin,
				isjoinrequest,
				isinvite,
				reluserid
			FROM ".$db->prefix."team_userrole
			WHERE userid=".bkint(Abricos::$user->id)." AND teamid=".bkint($teamid)."
				AND (ismember=1 OR isjoinrequest=1 OR isinvite=1)
			LIMIT 1
		");
		
		if ($isAdmin){
			// список пользователей которых пригласили или сделали запрос на 
			// вступление в группу
			// список доступен только админу группы
			array_push($arr, "
				SELECT 
					userid,
					ismember,
					isadmin,
					isjoinrequest,
					isinvite,
					reluserid
				FROM ".$db->prefix."team_userrole
				WHERE userid<>".bkint(Abricos::$user->id)." AND teamid=".bkint($teamid)." 
					AND ismember=0 AND (isjoinrequest=1 OR isinvite=1)
			");
		}
		
		// публичный список пользователей
		array_push($arr, "
			SELECT
				userid,
				ismember,
				isadmin,
				isjoinrequest,
				isinvite,
				reluserid
			FROM ".$db->prefix."team_userrole
			WHERE userid<>".bkint(Abricos::$user->id)." AND teamid=".bkint($teamid)." AND ismember=1
		");
		
		$sql = "
			SELECT
				ur.*,
				u.avatar,
				u.username,
				u.firstname,
				u.lastname
			FROM (
				".implode(" UNION ", $arr)."
			) ur
			INNER JOIN ".$db->prefix."user u ON ur.userid=u.userid
		";
		if ($memberid > 0){
			$sql .= "
				WHERE ur.userid=".bkint($memberid)."
				LIMIT 1
			";
		}
		return $db->query_read($sql);		
	}
	
	public static function Member(Ab_Database $db, $teamid, $isAdmin = false, $memberid = 0){
		$rows = TeamQuery::MemberList($db, $teamid, $isAdmin, $memberid);
		return $db->fetch_array($rows);
	}

		
	/**
	 * Список сообществ пользователя
	 * 
	 * @param Ab_Database $db
	 * @param integer $userid
	 * @param string $modname
	 * @param integer $currentUserId
	 * @param mixed $tids группы где текущий пользователь админ
	 */
	/*
	public static function TeamListByMember(Ab_Database $db, $userid, $modname = '', $currentUserId = 0, $admtms = ''){
		$isMyInfo = $userid == $currentUserId;
		
		$sql = "
			SELECT
				ur.teamid as id,
				ur.ismember as ismbr,
				ur.isadmin as isadm,
				".(!$isMyInfo ? "
					0 as isjrq,
					0 as isinv,
					0 as ruid
				" : "
					ur.isjoinrequest as isjrq,
					ur.isinvite as isinv,
					ur.reluserid as ruid
				")."
			FROM ".$db->prefix."team_userrole ur
			LEFT JOIN ".$db->prefix."team t ON ur.teamid=t.teamid
			WHERE ur.userid=".bkint($userid)." 
				AND (ismember=1 ".($isMyInfo ? " OR isinvite=1 OR isjoinrequest=1" : "").")
				".(!empty($modname) ? " AND t.module='".bkstr($modname)."'" : "")."
		";
		
		if (is_array($admtms) && count($admtms)>0){
			$arr = array();
			foreach($admtms as $tid){
				array_push($arr, "ur.teamid=".bkint($tid['id']));
			}
			
			$sql .= "
				UNION
				SELECT
					ur.teamid as id,
					ur.ismember as ismbr,
					ur.isadmin as isadm,
					ur.isjoinrequest as isjrq,
					ur.isinvite as isinv,
					ur.reluserid as ruid
				FROM ".$db->prefix."team_userrole ur
				LEFT JOIN ".$db->prefix."team t ON ur.teamid=t.teamid
				WHERE ur.userid=".bkint($userid)." AND (".implode(" OR ", $arr).")
					AND (ismember=1 OR isinvite=1 OR isjoinrequest=1)
					".(!empty($modname) ? " AND t.module='".bkstr($modname)."'" : "")."
			";
			
			$sql = "
				SELECT DISTINCT ut.*
				FROM (
					".$sql."
				) ut
			";
		}
		return $db->query_read($sql);
	}
/**/
	
	/**
	 * Список сообществ в которых пользователь является админом
	 * 
	 * @param Ab_Database $db
	 * @param integer $userid
	 */
	/*
	public static function TeamListByMemberIsAdmin(Ab_Database $db, $userid){
		$sql = "
			SELECT
				ur.teamid as id
			FROM ".$db->prefix."team_userrole ur
			LEFT JOIN ".$db->prefix."team t ON ur.teamid=t.teamid
			WHERE ur.userid=".bkint($userid)." AND ur.isadmin=1 AND t.deldate=0
		";
		return $db->query_read($sql);
	}
	
	/**/

	/**
	 * Пользователь userid просматривает группу teamid
	 * 
	 * @param Ab_Database $db
	 * @param integer $teamid
	 * @param integer $userid
	 */
	public static function UserTeamView(Ab_Database $db, $teamid){
		$sql = "
			INSERT INTO ".$db->prefix."team_userrole
			(teamid, userid, lastview, dateline, upddate) VALUES (
				".bkint($teamid).",
				".bkint(Abricos::$user->id).",
				".TIMENOW.",
				".TIMENOW.",
				".TIMENOW."
			) ON DUPLICATE KEY UPDATE
				lastview=".TIMENOW."
		";
		$db->query_write($sql);
	}

	/**
	 * Админ группы отправил приглашение на вступление пользователю userid
	 *
	 * @param Ab_Database $db
	 * @param integer $teamid
	 * @param integer $userid
	 */
	public static function MemberInviteSetWait(Ab_Database $db, $teamid, $userid, $adminid){
		$sql = "
			INSERT INTO ".$db->prefix."team_userrole
			(teamid, userid, reluserid, isinvite, dateline, upddate) VALUES (
				".bkint($teamid).",
				".bkint($userid).",
				".bkint($adminid).",
				1,
				".TIMENOW.",
				".TIMENOW."
			) ON DUPLICATE KEY UPDATE
				isinvite=1
		";
		$db->query_write($sql);
	}
	
	public static function MemberInviteWaitCountByTeam(Ab_Database $db, $teamid){
		$sql = "
			SELECT 
				count(*) as cnt
			FROM ".$db->prefix."team_userrole
			WHERE teamid=".bkint($teamid)." AND ismember=0 AND isinvite=1
		";
		$row = $db->query_first($sql);
		return intval($row['cnt']);
	}
	
	public static function MemberInviteWaitCountByUser(Ab_Database $db, $userid){
		$sql = "
			SELECT 
				count(*) as cnt
			FROM ".$db->prefix."team_userrole
			WHERE reluserid=".bkint($userid)." AND ismember=0 AND isinvite=1
		";
		$row = $db->query_first($sql);
		return intval($row['cnt']);
	}
	

	/**
	 * Пользователь принял приглашение вступить в группу
	 *
	 * @param Ab_Database $db
	 * @param integer $teamid
	 * @param integer $userid
	 */
	public static function MemberInviteSetAccept(Ab_Database $db, $teamid, $userid){
		$sql = "
			UPDATE ".$db->prefix."team_userrole
			SET 
				isinvite=2,
				ismember=1
			WHERE teamid=".bkint($teamid)." AND userid=".bkint($userid)." 
				AND ismember=0
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	/**
	 * Пользователь отклонил приглашение вступить в группу
	 * 
	 * @param Ab_Database $db
	 * @param integer $teamid
	 * @param integer $userid
	 */
	public static function MemberInviteSetReject(Ab_Database $db, $teamid, $userid){
		$sql = "
			UPDATE ".$db->prefix."team_userrole
			SET 
				isinvite=3,
				ismember=0
			WHERE teamid=".bkint($teamid)." AND userid=".bkint($userid)." 
				AND ismember=0
			LIMIT 1
		";
		$db->query_write($sql);
	}

	/**
	 * Пользователь userid сам запросил вступление в группу
	 *  
	 * @param Ab_Database $db
	 * @param integer $teamid
	 * @param integer $userid
	 */
	public static function MemeberJoinRequestSet(Ab_Database $db, $teamid, $userid){
		$sql = "
			INSERT INTO ".$db->prefix."team_userrole
			(teamid, userid, isjoinrequest, dateline, upddate) VALUES (
				".bkint($teamid).",
				".bkint($userid).",
				1,
				".TIMENOW.",
				".TIMENOW."
			) ON DUPLICATE KEY UPDATE
				isjoinrequest=1
		";
		$db->query_write($sql);
	}

	/**
	 * Пользователь userid стал членом группы teamid
	 * 
	 * @param Ab_Database $db
	 * @param integer $teamid
	 * @param integer $userid
	 */
	public static function UserSetMember(Ab_Database $db, $teamid, $userid){
		$sql = "
			INSERT INTO ".$db->prefix."team_userrole
			(teamid, userid, ismemeber, dateline, upddate) VALUES (
				".bkint($teamid).",
				".bkint($userid).",
				1,
				".TIMENOW.",
				".TIMENOW."
			) ON DUPLICATE KEY UPDATE
				ismember=1
		";
		$db->query_write($sql);
	}
	
	public static function UserByEmail(Ab_Database $db, $email){
		$sql = "
			SELECT
				u.userid as id,
				u.avatar as avt,
				u.username as unm,
				u.firstname as fnm,
				u.lastname as lnm
			FROM ".$db->prefix."user u
			WHERE u.email='".bkstr($email)."'
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function MyNameUpdate(Ab_Database $db, $userid, $d){
		$sql = "
			UPDATE ".$db->prefix."user
			SET
				firstname='".$d->firstname."',
				lastname='".$d->lastname."'
			WHERE userid=".bkint($userid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	
	
}


?>