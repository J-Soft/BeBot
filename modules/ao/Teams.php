<?php
/*
* Written by Zacix for BeBot
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2009 Thomas Juberg, ShadowRealm Creations and the BeBot development team.
*
* Developed by:
* - Alreadythere (RK2)
* - Blondengy (RK1)
* - Blueeagl3 (RK1)
* - Glarawyn (RK1)
* - Khalem (RK1)
* - Naturalistic (RK1)
* - Temar (RK1)
*
* See Credits file for all aknowledgements.
*
*  This program is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; version 2 of the License only.
*
*  This program is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  You should have received a copy of the GNU General Public License
*  along with this program; if not, write to the Free Software
*  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307
*  USA
*/
require_once ("Teams.inc");
$newteams = new NewTeams($bot);
/*
The Class itself...
*/
class NewTeams extends BaseActiveModule
{
	var $pgroup;
	var $numteams;
	var $teams;

	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
		$this->teams = array();
		$this->numteams = 0;
		$this->pgroup = array(array());
		$this->register_command("all", "teams", "GUEST");
		$this->register_command("all", "startteam", "LEADER");
		$this->register_command("all", "clearteams", "LEADER");
		$this->register_command("all", "addteam", "LEADER");
		$this->register_command("all", "remteam", "LEADER");
		$this->register_command("all", "delteam", "LEADER");
		$this->register_command("all", "setleader", "LEADER");
		$this->register_command("all", "teamadmin", "LEADER");
		$this->register_command("all", "teamname", "LEADER");
		$this->register_event("pgjoin");
		$this->register_event("pgleave");
	}

	function SetTeamLeader($name, $num, $executer)
	{
		if (preg_match("#^[1-9]#", $num))
		{
			if ($num <= count($this->teams) && $num > 0)
			{
				$team = & $this->GetTeam($num);
				$member = & $team->GetMember($name);
				if ($member)
				{
					$team->ClearLeader();
					$member->SetLeader(true);
					$this->bot->send_pgroup("[##highlight##" . $name . "##end##] is new leader of team ##highlight##" . $num . "##end##");
				}
				else
				{
					$this->bot->send_tell($executer, "[##highlight##" . $name . "##end##] is not in team ##highlight##" . $num . "##end##");
				}
			}
			else
			{
				$this->bot->send_tell($executer, "Team ##highlight##" . $num . "##end## does not exist");
			}
		}
		else
		{
			$this->bot->send_tell($executer, "Usage: !setleader &lt;number&gt; &lt;name&gt;");
		}
	}

	function PrintTeams()
	{
		foreach ($this->teams as $index => $team)
		{
			if ($team->GetName() == "")
			{
				$msg = "Team ##highlight##" . bcadd($index, 1) . "##end## (##highlight##" . $team->Count() . "##end##) :: ";
			}
			else
			{
				$msg = "Team ##highlight##" . bcadd($index, 1) . "##end## :: \"" . $team->GetName() . "\" (##highlight##" . $team->Count() . "##end##) :: ";
			}
			$members = & $team->GetTeamMembers();
			foreach ($members as $index => $teammember)
			{
				if ($teammember->IsLeader())
				{
					$msg .= "[##blob_title##" . $teammember->GetName() . " :: Leader##end##]";
				}
				else
				{
					$msg .= "[##highlight##" . $teammember->GetName() . "##end##]";
				}
			}
			$msg .= "##end##";
			$this->bot->send_pgroup($msg);
		}
	}

	function AddTeam(&$team)
	{
		$this->teams[] =& $team;
	}

	function ClearTeams($name)
	{
		foreach ($this->teams as $index => $teams)
		{
			$this->DelTeam(1, $name);
		}
		$this->bot->send_pgroup("Teams cleared by [##highlight##" . $name . "##end##]");
	}

	function DelTeam($num, $executer)
	{
		if (preg_match("#^[1-9]#", $num))
		{
			if ($num <= count($this->teams) && $num > 0)
			{
				$team = & $this->teams[$num - 1];
				foreach ($team->GetTeamMembers() as $index => $member)
				{
					if (isset($this->pgroup[$member->GetName()]))
					{
						$this->pgroup[$member->GetName()][4] = true;
					}
				}
				unset($this->teams[$num - 1]);
				$this->numteams --;
				$this->teams = array_values($this->teams);
			}
			else
			{
				$this->bot->send_tell($executer, "Team ##highlight##" . $num . "##end## does not exist");
			}
		}
		else
		{
			$this->bot->send_tell($executer, "Usage: <pre>delteam &lt;number&gt;##end##");
		}
	}

	function &GetTeam($num)
	{
		return $this->teams[$num - 1];
	}

	function DelTeamMember($name, $executer, $suppress = true, $silent = false)
	{
		$return = false;
		if ($this->bot->core("chat")->get_uid($name))
		{
			for ($i = 1; $i <= count($this->teams); $i ++)
			{
				$team = & $this->GetTeam($i);
				if ($team->DelMember($name))
				{
					if (isset($this->pgroup[$name]))
					{
						$this->pgroup[$name][4] = true;
					}
					$return = true;
					if (! $suppress)
					{
						$this->bot->send_pgroup("[##highlight##" . $name . "##end##] has been removed from team ##highlight##" . $i . "##end##");
					}
				}
				else
				{
					$return = $return | false;
				}
			}
			return $return;
		}
		else
		{
			if (! $silent)
			{
				$this->bot->send_tell($executer, "Usage: <pre>remteam &lt;name&gt;");
			}
			return false;
		}
	}

	function AddTeamMember($name, $num, $executer)
	{
		if (preg_match("#^[1-9]#", $num))
		{
			if ($num <= count($this->teams) && $num > 0)
			{
				if ($name != "")
				{
					if (isset($this->pgroup[$name]))
					{
						$member = new TeamMember($this->pgroup[$name][0], $this->pgroup[$name][1], $this->pgroup[$name][2], $this->pgroup[$name][3]);
						$team = & $this->GetTeam($num);
						$teammembers = & $team->GetTeamMembers();
						print_r(get_object_vars($teammembers));
						if (array_search($member, $teammembers))
						{
							$this->bot->send_pgroup("##highlight##" . $name . "##end## is already in team ##highlight##" . $num . "##end##");
							return;
						}
						else
						{
							$deleted = $this->DelTeamMember($name, $executer);
						}
						if ($team->AddMember($member))
						{
							$this->pgroup[$name][4] = false;
							if ($deleted)
							{
								$this->bot->send_pgroup("[##highlight##" . $name . "##end##] changed to team ##highlight##" . $num . "##end##");
							}
							else
							{
								$this->bot->send_pgroup("[##highlight##" . $name . "##end##] has been added to team ##highlight##" . $num . "##end####end##");
							}
						}
						else
						{
							$this->bot->send_pgroup("Team ##highlight##" . $num . "##end## is full");
						}
					}
					else
					{
						$this->bot->send_tell($executer, "[##highlight##" . $name . "##end##] is not in the group");
					}
				}
			}
			else
			{
				$this->bot->send_tell($executer, "Team ##highlight##" . $num . "##end## does not exist");
			}
		}
		else
		{
			$this->bot->send_tell($executer, "Usage: <pre>teamadd &lt;number&gt; &lt;name&gt;");
		}
	}

	function StartTeam($executer, $name, $teamname)
	{
		if ($name != "")
		{
			if (isset($this->pgroup[$name]))
			{
				$this->DelTeamMember($name, $executer);
				$member = new TeamMember($this->pgroup[$name][0], $this->pgroup[$name][1], $this->pgroup[$name][2], $this->pgroup[$name][3], true);
				$team = new Team($member, $teamname);
				++ $this->numteams;
				$this->pgroup[$name][4] = false;
				$this->AddTeam($team);
				$this->bot->send_pgroup("Team ##highlight##" . $this->numteams . "##end## has been started. Leader is ##highlight##[" . $name . "##end##]");
			}
			else
			{
				$this->bot->send_pgroup("[##highlight##" . $name . "##end##] is not in the group");
			}
		}
	}

	function SetTeamName($teamname, $num, $executer)
	{
		if (preg_match("#^[1-9]#", $num))
		{
			if ($num <= count($this->teams) && $num > 0)
			{
				$team = & $this->GetTeam($num);
				$team->SetName($teamname);
			}
			else
			{
				$this->bot->send_tell($executer, "Team ##highlight##" . $num . "##end## does not exist");
			}
		}
		else
		{
			$this->bot->send_tell($executer, "Usage: <pre>teamname &lt;number&gt; &lt;name&gt;");
		}
	}

	function GetPlayerInfo($name)
	{
		$uid = $this->bot->core("player")->id($name);
		$result = $this->bot->core("whois")->lookup($name);
		if ($result instanceof BotError)
		{
			$member[0] = $name;
			$member[1] = "Unknown";
			$member[2] = "Unknown";
			$member[3] = "Unknown";
			$member[4] = true;
			return $member;
		}
		else
		{
			$member[0] = $name;
			$member[1] = $result["level"];
			$member[2] = $result["profession"];
			$member[3] = $result["guild"];
			$member[4] = true;
			return $member;
		}
	}

	function command_handler($name, $msg, $origin)
	{
		$msg = explode(" ", $msg);
		$msg[0] = strtolower($msg[0]);
		if (strtolower($msg[0]) == "startteam")
		{
			$this->StartTeam($name, ucfirst(strtolower($msg[1])), implode(" ", array_slice($msg, 2)));
		}
		else if ($msg[0] == "teams")
		{
			$this->PrintTeams();
		}
		else if ($msg[0] == "addteam")
		{
			$this->AddTeamMember(ucfirst(strtolower($msg[2])), $msg[1], $name);
		}
		else if ($msg[0] == "remteam")
		{
			$this->DelTeamMember(ucfirst(strtolower($msg[1])), $name, false);
		}
		else if ($msg[0] == "delteam")
		{
			$this->DelTeam($msg[1], $name);
		}
		else if ($msg[0] == "setleader")
		{
			$this->SetTeamLeader(ucfirst(strtolower($msg[2])), $msg[1], $name);
		}
		else if ($msg[0] == "teamname")
		{
			$this->SetTeamName(implode(" ", array_slice($msg, 2)), $msg[1], $name);
		}
		else if ($msg[0] == "clearteams")
		{
			$this->ClearTeams($name);
		}
		else if ($msg[0] == "teamadmin")
		{
			$this->GetAdminConsole($name);
		}
		return false;
	}

	function pgjoin($name)
	{
		$this->pgroup[$name] = $this->GetPlayerInfo($name);
	}

	function pgleave($name)
	{
		unset($this->pgroup[$name]);
		$this->DelTeamMember($name, "", 0, 1);
	}

	function GetAdminConsole($name)
	{
		$msg = "##highlight##:::: Teams Administration ::::##end##\n\n";
		$msg .= "##highlight##Commands:##end##\n";
		$msg .= $this->bot->core("tools")->chatcmd("teamadmin", "Refresh Teams Administration") . "\n";
		$msg .= $this->bot->core("tools")->chatcmd("teams", "Show Teams") . "\n";
		$msg .= $this->bot->core("tools")->chatcmd("clearteams", "Clear Teams") . "\n\n";
		foreach ($this->teams as $index => $team)
		{
			if ($team->GetName() == "")
			{
				$msg .= ":: Team ##highlight##" . bcadd($index, 1) . "##end## (##highlight##" . $team->Count() . "##end##) :: ";
				$msg .= $this->bot->core("tools")->chatcmd("delteam " . bcadd($index, 1), "Del") . "\n";
			}
			else
			{
				$msg .= ":: Team ##highlight##" . bcadd($index, 1) . "##end## :: \"" . $team->GetName() . "\" (##highlight##" . $team->Count() . "##end##) :: ";
				$msg .= $this->bot->core("tools")->chatcmd("delteam " . bcadd($index, 1), "Del") . "\n";
			}
			$members = & $team->GetTeamMembers();
			foreach ($members as $indexteam => $teammember)
			{
				if ($teammember->IsLeader())
				{
					$msg .= "\t[##blob_title##" . $teammember->GetName() . " :: Leader##end##] (" . $teammember->GetLevel() . " " . $teammember->GetProfession() . ") ";
					$msg .= $this->GetExtraInfo($teammember->GetName(), $index + 1);
				}
				else
				{
					$msg .= "\t[##highlight##" . $teammember->GetName() . "##end##] (" . $teammember->GetLevel() . " " . $teammember->GetProfession() . ") ";
					$msg .= $this->GetExtraInfo($teammember->GetName(), $index + 1);
				}
				foreach ($this->teams as $indexteam => $teams)
				{
					$msg .= "/";
					$msg .= $this->bot->core("tools")->chatcmd("addteam " . bcadd($indexteam, 1) . " " . $teammember->GetName(), bcadd($indexteam, 1));
				}
				$msg .= "\n";
			}
			$msg .= "\n";
		}
		$msg .= "\n";
		$msg .= "##blob_title##::Looking For Team::##end##\n\n";
		foreach ($this->pgroup as $index => $array)
		{
			$test = $array;
			if ($array[4])
			{
				$msg .= "\t[##highlight##" . $array[0] . "##end##] (" . $array[1] . " " . $array[2] . ") :: ";
				$msg .= $this->bot->core("tools")->chatcmd("startteam " . $array[0], "Start Team");
				foreach ($this->teams as $indexteam => $teams)
				{
					$msg .= "/";
					$msg .= $this->bot->core("tools")->chatcmd("addteam " . bcadd($indexteam, 1) . " " . $test[0], bcadd($indexteam, 1));
				}
				$msg .= "\n";
			}
		}
		$msg .= "##end##";
		$this->bot->send_tell($name, $this->bot->core("tools")->make_blob("Teams Administration", $msg));
	}

	function GetExtraInfo($name, $index)
	{
		$msg .= $this->bot->core("tools")->chatcmd("remteam " . $name, "Rem") . "/";
		$msg .= $this->bot->core("tools")->chatcmd("startteam " . $name, "Sta") . "/";
		$msg .= $this->bot->core("tools")->chatcmd("setleader $index $name", "Lead");
		return $msg;
	}
}
?>
