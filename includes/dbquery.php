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
	
	public static function TeamModuleNameList(Ab_Database $db){
		$sql = "
			SELECT 
				DISTINCT t.module as m
			FROM ".$db->prefix."team t
			WHERE t.deldate=0 
		";
		return $db->query_read($sql);
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
	
	public static function UserRoleUpdate(Ab_Database $db, $teamid, $userid, $ismember, $isadmin){
		$sql = "
			INSERT INTO ".$db->prefix."team_userrole
			(teamid, userid, ismember, isadmin, dateline, upddate) VALUES (
				".bkint($teamid).",
				".bkint($userid).",
				".bkint($ismember).",
				".bkint($isadmin).",
				".TIMENOW.",
				".TIMENOW."
			) ON DUPLICATE KEY UPDATE
				ismember=".bkint($ismember).",
				isadmin=".bkint($isadmin).",
				upddate=".TIMENOW."
		";
		$db->query_write($sql);
	}
	
	/**
	 * Пользователь userid просматривает группу teamid
	 * 
	 * @param Ab_Database $db
	 * @param integer $teamid
	 * @param integer $userid
	 */
	public static function TeamViewUser(Ab_Database $db, $teamid){
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