<?php
/**
 * @package Abricos
 * @subpackage Team
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

class TeamQuery {
	
	const FILECLEARTIME = 86400;
	
	public static function TeamModuleName(Ab_Database $db, $teamid){
		$sql = "
			SELECT t.module as m
			FROM ".$db->prefix."team t
			WHERE t.deldate=0 AND t.teamid=".bkint($teamid)."
			LIMIT 1
		";
		$row = $db->query_first($sql);
		if (empty($row)){ return null; }
		
		return $row['m'];
	}
	
	/**
	 * Список групп с правами текущего пользователя на эти группы
	 *
	 * @param Ab_Database $db
	 * @param string $module имя модуля
	 */
	public static function TeamList(TeamManager $man, $page = 1, $memberid = 0, $teamid = 0){
		$db = $man->db;
		$module = $man->moduleName;
		$memberid = intval($memberid);
		$teamid = intval($teamid);
		
		$sql = "
			SELECT
				t.teamid as id,
				t.module as m,
				t.teamtype as tp,
				t.ismoder as mdr,
				t.userid as auid,
				t.title as tl,
				t.email as eml,
				t.descript as dsc,
				t.site,
				t.logo,
				t.isanyjoin as anj,
				t.membercount as mcnt
		";
		
		if (Abricos::$user->id > 0){
			$sql .= "
				,ur.ismember
				,ur.isadmin
				,ur.isjoinrequest
				,ur.isinvite
				,ur.reluserid
				,ur.isremove
			";

			foreach($man->fldExtTeamUserRole as $key => $value){
				$far = explode(",", $value);
				foreach($far as $f){
					$sql .= " ,".$key.".".trim($f)." ";
				}
			}
		}
		
		if ($teamid > 0){
			foreach($man->fldExtTeamDetail as $key => $value){
				$far = explode(",", $value);
				foreach($far as $f){
					$sql .= " ,".$key.".".trim($f)." ";
				}
			}
		}
		
		$sql .= "
			FROM ".$db->prefix."team t
		";
		
		if (Abricos::$user->id > 0){
			$sql .= "
				LEFT JOIN ".$db->prefix."team_userrole ur ON t.teamid=ur.teamid
					AND ur.userid=".bkint(Abricos::$user->id)."
			";
			
			foreach($man->fldExtTeamUserRole as $key => $value){
				$sql .= "
					LEFT JOIN ".$db->prefix.$key." ".$key." ON t.teamid=".$key.".teamid
						AND ".$key.".userid=".bkint(Abricos::$user->id)."
				";
			}
		}
		if ($teamid > 0){
			foreach($man->fldExtTeamDetail as $key => $value){
				$sql .= "
					LEFT JOIN ".$db->prefix.$key." ".$key." ON t.teamid=".$key.".teamid
				";
			}
				
		}
		if ($memberid>0){
			$sql .= "
				LEFT JOIN ".$db->prefix."team_userrole urm ON t.teamid=urm.teamid
					AND urm.userid=".bkint($memberid)."
			";
		}
	
		$sql .= "
			WHERE t.deldate=0
		";
		
		/*
		// отключена дополнительная проверка на принадлежность сообещства к вызывающему модулю
		$sql .= "
			WHERE t.deldate=0 AND t.module='".bkstr($module)."'
		";
		/**/
		
		if (!$man->IsAdminRole()){
			// не админу доступны только группы прошедшии модерацию или ожидающие модерацию,
			// но только для авторов
			if (Abricos::$user->id > 0){
				$sql .= "
					AND (t.ismoder=0 OR (t.ismoder=1 AND t.userid=".bkint(Abricos::$user->id)."))
				";
			}else{
				$sql .= "
					AND t.ismoder=0
				";
			}
		}
		
		if ($memberid>0){
			$sql .= "
				AND urm.ismember=1
			";
		}
		if ($teamid > 0){
			$sql .= "
				AND t.teamid=".bkint($teamid)."
				LIMIT 1
			";
		}

		return $db->query_read($sql);
	}
	
	/**
	 * Группа с правами текущего пользователя на неё
	 *
	 * @param TeamManager $man
	 * @param unknown_type $teamid
	 */
	public static function Team(TeamManager $man, $teamid){
		$rows = TeamQuery::TeamList($man, 1, 0, $teamid);
		while (($row = $man->db->fetch_array($rows))){
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
		$cnt = intval($row['cnt']);
		$sql = "
			UPDATE ".$db->prefix."team
			SET membercount=".$cnt."
			WHERE teamid=".bkint($teamid)." 
			LIMIT 1
		";
		$db->query_write($sql);
		return $cnt;
	}
	
	public static function TeamAppend(Ab_Database $db, $module, $userid, $isModer, $d){
		$sql = "
			INSERT INTO ".$db->prefix."team
				(module, teamtype, userid, ismoder, title, email, descript, site, logo, isanyjoin, dateline, upddate) VALUES (
				'".bkstr($module)."',
				'".bkstr($d->tp)."',
				".bkint($userid).",
				".($isModer ? 1 : 0).",
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
				teamtype='".bkstr($d->tp)."',
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
	public static function MemberList(TeamManager $man, Team $team, $memberid = 0){
		$db = $man->db;
		$flds = "";
		$ljoin = "";
		
		foreach($man->fldExtTeamUserRole as $key => $value){
			$ljoin .= "
				LEFT JOIN ".$db->prefix.$key." ".$key." ON ur.teamid=".$key.".teamid
					AND ".$key.".userid=ur.userid
			";
				
			$far = explode(",", $value);
			foreach($far as $f){
				$flds .= " ,".$key.".".trim($f)." ";
			}
		}
		
		$arr = array();
		
		// этот пользователь в этом списке
		array_push($arr, "
			SELECT
				ur.userid as id,
				ur.ismember,
				ur.isadmin,
				ur.isjoinrequest,
				ur.isinvite,
				ur.reluserid,
				u.isvirtual
				".$flds."
			FROM ".$db->prefix."team_userrole ur
			INNER JOIN ".$db->prefix."user u ON u.userid=ur.userid
				".$ljoin."
			WHERE ur.userid=".bkint(Abricos::$user->id)." AND ur.teamid=".bkint($team->id)."
				AND (ur.ismember=1 OR ur.isjoinrequest=1 OR ur.isinvite=1) AND ur.isremove=0
			LIMIT 1
		");
		
		if ($team->role->IsAdmin()){
			// список пользователей которых пригласили или сделали запрос на 
			// вступление в группу
			// список доступен только админу группы
			array_push($arr, "
				SELECT 
					ur.userid as id,
					ur.ismember,
					ur.isadmin,
					ur.isjoinrequest,
					ur.isinvite,
					ur.reluserid,
					u.isvirtual
					".$flds."
				FROM ".$db->prefix."team_userrole ur
				INNER JOIN ".$db->prefix."user u ON u.userid=ur.userid
					".$ljoin."
				WHERE ur.userid<>".bkint(Abricos::$user->id)." AND ur.teamid=".bkint($team->id)." 
					AND ur.ismember=0 AND (ur.isjoinrequest=1 OR ur.isinvite=1) AND ur.isremove=0
			");
		}
		
		// публичный список пользователей
		array_push($arr, "
			SELECT
				ur.userid as id,
				ur.ismember,
				ur.isadmin,
				ur.isjoinrequest,
				ur.isinvite,
				ur.reluserid,
				u.isvirtual
				".$flds."
			FROM ".$db->prefix."team_userrole ur
			INNER JOIN ".$db->prefix."user u ON u.userid=ur.userid
				".$ljoin."
			WHERE ur.userid<>".bkint(Abricos::$user->id)." AND ur.teamid=".bkint($team->id)." 
				AND ur.ismember=1 
		");
		
		$sql = "
			SELECT 
				DISTINCT *
			FROM (
				".implode(" UNION ", $arr)."
			) urm
		";
		if ($memberid > 0){
			$sql .= "
				WHERE urm.id=".bkint($memberid)."
				LIMIT 1
			";
		}

		return $db->query_read($sql);
	}
	
	public static function Member(TeamManager $man, $team, $memberid){
		$rows = TeamQuery::MemberList($man, $team, $memberid);
		return $man->db->fetch_array($rows);
	}
	
	public static function MemberInGroupList(Ab_Database $db, $teamid, $moduleName){
		$sql = "
			SELECT 
				mg.groupid as gid,
				mg.userid as uid
			FROM ".$db->prefix."team_membergroup g
			INNER JOIN ".$db->prefix."team_memberingroup mg ON g.groupid=mg.groupid
			WHERE g.teamid=".bkint($teamid)." AND g.module='".bkstr($moduleName)."' AND g.deldate=0
		";
		return $db->query_write($sql);
	}
	
	public static function MemberAddToGroup(Ab_Database $db, $groupid, $memberid){
		$sql = "
			INSERT IGNORE INTO ".$db->prefix."team_memberingroup (groupid, userid, dateline) VALUES (
				".bkint($groupid).",
				".bkint($memberid).",
				".TIMENOW."
			)
		";
		$db->query_write($sql);
	}
	
	public static function MemberRemoveFromGroup(Ab_Database $db, $groupid, $memberid){
		$sql = "
			DELETE FROM ".$db->prefix."team_memberingroup 
			WHERE groupid=".bkint($groupid)." AND memberid=".bkint($memberid)."
		";
		$db->query_write($sql);
	}
	
	public static function MemberGroupList(Ab_Database $db, $teamid, $moduleName){
		$sql = "
			SELECT
				g.groupid as id,
				g.parentgroupid as pid,
				g.title as tl
			FROM ".$db->prefix."team_membergroup g
			WHERE g.teamid=".bkint($teamid)." AND g.module='".bkstr($moduleName)."' AND g.deldate=0
		";
		return $db->query_read($sql);
	}

	public static function MemberGroupAppend(Ab_Database $db, $teamid, $moduleName, $d){
		$sql = "
			INSERT INTO ".$db->prefix."team_membergroup (teamid, module, title, dateline) VALUES (
				".bkint($teamid).",
				'".$moduleName."',
				'".$d->tl."',
				".TIMENOW."
			)
		";
		$db->query_write($sql);
		return $db->insert_id();
	}
	
	public static function MemberGroupUpdate(Ab_Database $db, $teamid, $moduleName, $memberGroupId, $d){
		$sql = "
			UPDATE ".$db->prefix."team_membergroup
			SET title='".$d->tl."',
				upddate=".TIMENOW."
			WHERE teamid=".bkint($teamid)." AND module='".bkstr($moduleName)."' AND groupid=".bkint($memberGroupId)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
		
	/**
	 * Список сообществ пользователя
	 * 
	 * @param Ab_Database $db
	 * @param integer $userid
	 * @param string $moduleName
	 * @param integer $currentUserId
	 * @param mixed $tids группы где текущий пользователь админ
	 */
	/*
	public static function TeamListByMember(Ab_Database $db, $userid, $moduleName = '', $currentUserId = 0, $admtms = ''){
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
				".(!empty($moduleName) ? " AND t.module='".bkstr($moduleName)."'" : "")."
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
					".(!empty($moduleName) ? " AND t.module='".bkstr($moduleName)."'" : "")."
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
				reluserid=".bkint($adminid).",
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

	public static function MemberRemove(Ab_Database $db, $teamid, $userid){
		$sql = "
			UPDATE ".$db->prefix."team_userrole
			SET
				isremove=".(Abricos::$user->id == $userid ? 2 : 1).",
				ismember=0
			WHERE teamid=".bkint($teamid)." AND userid=".bkint($userid)."
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
			(teamid, userid, ismember, dateline, upddate) VALUES (
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

	public static function UserByIds(Ab_Database $db, $ids){
		if (count($ids) == 0){ return; }
		
		$wh = array();
		for ($i=0; $i<count($ids); $i++){
			array_push($wh, "u.userid=".bkint($ids[$i]));
		}
		$sql = "
			SELECT
				u.userid as id,
				u.avatar as avt,
				u.username as unm,
				u.firstname as fnm,
				u.lastname as lnm,
				u.isvirtual as vrt
			FROM ".$db->prefix."user u
			WHERE ".implode(" OR ", $wh)."
		";
		return $db->query_read($sql);
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
	
	public static function FileAddToBuffer(Ab_Database $db, $userid, $fhash, $fname){
		$sql = "
			INSERT INTO ".$db->prefix."team_filebuffer (userid, filehash, filename, dateline) VALUES (
				".bkint($userid).",
				'".bkstr($fhash)."',
				'".bkstr($fname)."',
				".TIMENOW."
			)
		";
		$db->query_write($sql);
	}
	
	public static function FileBufferCheck(Ab_Database $db, $fhash){
		$sql = "
			SELECT
				fileid as id,
				filehash as fh
			FROM ".$db->prefix."team_filebuffer
			WHERE filehash='".bkstr($fhash)."'
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function FileRemoveFromBuffer(Ab_Database $db, $fhash){
		$sql = "
			DELETE FROM ".$db->prefix."team_filebuffer
			WHERE filehash='".bkstr($fhash)."'
		";
		return $db->query_read($sql);
	}
	
	public static function FileFreeFromBufferList(Ab_Database $db){
		$sql = "
			SELECT
				fileid as id,
				filehash as fh
			FROM ".$db->prefix."team_filebuffer
			WHERE dateline<".(TIMENOW-TeamQuery::FILECLEARTIME)."
		";
		return $db->query_read($sql);
	}
	
	public static function FileFreeListClear(Ab_Database $db){
		$sql = "
			DELETE FROM ".$db->prefix."team_filebuffer
			WHERE dateline<".(TIMENOW-TeamQuery::FILECLEARTIME)."
		";
		return $db->query_read($sql);
	}
	

}


?>