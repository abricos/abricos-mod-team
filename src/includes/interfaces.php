<?php
/**
 * @package Abricos
 * @subpackage Team
 * @copyright 2013-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Interface ITeamOwnerApp
 */
interface ITeamOwnerApp {
    public function IsAdminRole();

    public function IsTeamAppendRole();

    public function IsWriteRole();

    public function IsViewRole();

    public function Team_OnTeamSave(TeamSave $r);

    public function Team_OnTeam(Team $team);

    public function Team_OnMemberList(TeamMemberList $memberList);

    public function Team_OnMember(TeamMember $member);

    public function Team_OnMemberSave(TeamMemberSave $r);
}