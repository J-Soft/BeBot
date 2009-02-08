<?php
/*
* Raid.php - Announces a raid.
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2007 Thomas Juberg Stensås, ShadowRealm Creations and the BeBot development team.
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
*
* File last changed at $LastChangedDate: 2008-11-30 23:09:06 +0100 (Sun, 30 Nov 2008) $
* Revision: $Id: Raid.php 1833 2008-11-30 22:09:06Z alreadythere $
*/


$raid = new Raid($bot);

/*
The Class itself...
*/
class Raid extends BaseActiveModule
{
	var $raid;
	var $user;
	var $announce;
	var $start;
	var $locked;
	var $paused = false;



	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> raid = false;
		$this -> user = array();
		$this -> announce = 0;
		$this -> locked = false;

		$this -> register_command("tell", "c", "LEADER");
		$this -> register_command("all", "raid", "GUEST");
        $this -> register_event("pgleave");
		$this -> register_event("connect");
 
		if($this -> bot -> game == "ao")
			$this -> bot -> core("settings") -> create("Raid", "Remonleave", "On", "Automatically remove players from the raid if they leave <botname>'s channel?", "On;Off", FALSE, 15);
		$this -> bot -> core("settings") -> create("Raid", "Command", "LEADER", "Who should be able to access the higher level raid commands (all commands except join/leave)?", "ADMIN;LEADER;MEMBER;GUEST;ANONYMOUS");
		$this -> bot -> core("settings") -> create("Raid", "Cformat", "Raid Command from ##highlight####name####end##: ##msg##", "How Should the Raid Command be Output, Use ##name## and ##msg## to place name and message where you want.");
		$this -> bot -> core("settings") -> create("Raid", "Points", 0.1, "How Many points should a User get Every minuite while in Raid");

		$this -> help['description'] = 'Module to manage and announce raids.';
		$this -> help['command']['raid start <description>']="Starts a raid with optional description.";
		$this -> help['command']['raid end'] = "Ends a raid.";
		$this -> help['command']['raid join'] = "Join the active raid.";
		$this -> help['command']['raid leave'] = "Leave the active raid.";
		$this -> help['command']['raid reward <points>'] = "Reward <points> to all raiders.";
		$this -> help['command']['raid [lock/unlock]'] = "Locks or Unlocks the active raid.";
		$this -> help['command']['raid add <name>'] = "Adds player <name> to the current raid, even if the raid is locked.";
		$this -> help['command']['raid kick <name>'] = "Kicks player <name> from the current raid.";
		$this -> help['command']['raid check'] = "Generates a list of active raiders with assist links in a window for attendance checking.";
		$this -> help['command']['c <message>'] = "Raid command. Display <message> in a highly visiable manner.";
		$this -> help['notes'] = "All commands except join and leave are restricted to users with " . $this -> bot -> core("settings") -> get('Raid', 'Command') . " or higher access.";
	}


	function command_handler($name, $msg, $type)
	{
		$var = explode (" ", $msg, 2);

		switch($var[0])
		{
			case 'c':
				$this -> raid_command($name, $var[1]);
				Break;
			case 'raid':
				$var = explode(" ", $msg, 3);
				switch($var[1])
				{
					case 'start':
						$this -> bot -> send_output($name, $this -> start_raid($name, $var[2]), $type);
						Break;
					case 'stop':
					case 'end':
						$this -> bot -> send_output($name, $this -> end_raid($name), $type);
						Break;
					case 'join':
						$this -> bot -> send_output($name, $this -> join_raid($name), $type);
						Break;
					case 'leave':
						$this -> bot -> send_output($name, $this -> leave_raid($name), $type);
						Break;
					case 'kick':
						$this -> bot -> send_output($name, $this -> kick_raid($name, $var[2]), $type);
						Break;
					case 'check':
						$this -> bot -> send_output($name, $this -> check_raid($name), $type);
						Break;
					case 'lock':
					case 'unlock':
						$this -> bot -> send_output($name, $this -> lock_raid($name, $var[1]), $type);
						Break;
					case 'add':
						$this -> bot -> send_output($name, $this -> addto_raid($name, $var[2]), $type);
						Break;
					case 'reward':
					case 'give':
						$this -> add_point($name, $var[2]);
						Break;
					case 'pause':
						$this -> pause(TRUE);
						Break;
					case 'unpause':
						$this -> pause(FALSE);
						Break;
					Default:
						$this -> bot -> send_help($name, "raid");
				}
				Break;
			Default:
				$this -> bot -> send_output($name, "##error##Error : Broken plugin, Raid.php recieved unhandled command: ".$var[0]."##end##", $type);
		}
	}

	/*
	This gets called on cron
	*/
	function connect()
	{
		$this -> bot -> db -> query("UPDATE #___raid_points SET raiding = 0");
	}

	/*
	This gets called if someone leaves the privgroup
	*/
	function pgleave($name)
	{
		if ($this -> bot -> core("settings") -> get("Raid", "Remonleave"))
		{
			if (isset($this -> user[$name]))
			{
				unset($this -> user[$name]);
				return "##highlight##$name##end## was removed from the raid.";
				$this -> bot -> db -> query("UPDATE #___raid_points SET raiding = 0 WHERE id = " . $this -> points_to($name));
			}
		}
	}

	/*
	Starts a Raid
	*/
	function start_raid($name, $desc)
	{
		if ($this -> bot -> core("security") -> check_access($name, $this -> bot -> core("settings") -> get('Raid', 'Command')))
		{
			if (!$this -> raid)
			{
				$this -> description = $desc;
				$this -> raid = true;
				$this -> start = time();
				$this -> bot -> send_output($name, "##highlight##$name##end## has started the raid :: " . $this -> clickjoin(), "both");
				$this -> pause(TRUE);
				$this -> register_event("cron", "1min");
				return "Raid started.";
			}
			else
				return "Raid already running.";
		}
		else
			return "You must be a " . $this -> bot -> core("settings") -> get('Raid', 'Command') . " to start a raid";
	}



	/*
	Ends a Raid
	*/
	function end_raid($name)
	{
		if ($this -> bot -> core("security") -> check_access($name, $this -> bot -> core("settings") -> get('Raid', 'Command')))
		{
			if ($this -> raid)
			{
				$this -> raid = false;
				$this -> user = array();
				$this -> announce = 0;
				$this -> unregister_event("cron", "1min");
				$this -> bot -> send_output($name, "##highlight##$name##end## has stopped the raid.", "both");
				$this -> bot -> db -> query("UPDATE #___raid_points SET raiding = 0");
				$this -> locked = false;
				Return "Raid stopped.";
			}
			else
				return "No raid running.";
		}
		else
			return "You must be a " . $this -> bot -> core("settings") -> get('Raid', 'Command') . " to do this";
	}

	/*
	Issues a raid command
	*/
	function raid_command($name, $command)
	{
		if ($this -> bot -> core("security") -> check_access($name, $this -> bot -> core("settings") -> get('Raid', 'Command')))
		{
			$msg = $this -> bot -> core("settings") -> get('Raid', 'Cformat');
			$msg = str_replace("##name##", $name, $msg);
			$msg = str_replace("##msg##", $command, $msg);
			$this -> bot -> send_output($name, $msg, "both");
		}
		else
			$this -> bot -> send_tell($name, "You must be a ".$this -> bot -> core("settings") -> get('Raid', 'Command')." to do this");
	}

	/*
	Adds a point to all raiders
	*/
	function add_point($name, $points)
	{ //fix me! - fixed addto_raid so that raiders are added correctly
		if(!is_numeric($points))
		{
			$this -> bot -> send_tell($name, "Invalid Points Amount");
		}
		elseif ($this -> bot -> core("security") -> check_access($name, $this -> bot -> core("settings") -> get('Raid', 'Command')))
		{
			$this -> bot -> send_output("", "##highlight##$points##end## points have been added to all raiders.", "both");
			$this -> bot -> db -> query("UPDATE #___raid_points SET points = points + " . $points . " WHERE raiding = 1");
		}
		else
			$this -> bot -> send_tell($name, "You must be a raidleader to do this");
	}


	/*
	Adds a player to Raid
	*/
	function addto_raid($name, $player)
	{
		$player = ucfirst(strtolower($player));
		if ($this -> bot -> core("security") -> check_access($name, $this -> bot -> core("settings") -> get('Raid', 'Command')))
		{
			if (!$this -> raid)
				return "No raid in progress";
			else if (isset($this -> user[$player]))
				return $player." is already in the raid";
			$uid = $this -> bot -> core("chat") -> get_uid($player);
			if (!$uid)
				return "Player ##highlight##$player##end## does not exist.";
			else
			{
				$this -> bot -> db -> query("INSERT INTO #___raid_points (id, points, raiding) VALUES (" . $this -> points_to($player) . ", 0, 1) ON DUPLICATE KEY UPDATE raiding = 1");

				//Update last_raid
				$query = "UPDATE #___users SET last_raid = " . time() . " WHERE nickname = '$player'";
				$this -> bot -> db -> query($query);

				$this -> user[$player] = $uid;
				$this -> bot -> send_tell($player, "##highlight##$name##end## added you to the raid.");
				$this -> bot -> send_output("", "##highlight##$player##end## was ##highlight##added##end## to the raid by ##highlight##$name##end## :: " . $this -> clickjoin(), "both");
				return "##highlight##$player##end## has been ##highlight##added##end## to the raid";
			}
		}
	}


	/*
	Joins a Raid
	*/
	function join_raid($name)
	{
		if (isset($this -> user[$name]))
		{
			return "You are already in the raid";
		}
		else if ($this -> locked)
		{
			return "The raid status is currently ##highlight##locked##end##.";
		}
		else if ($this -> raid)
		{
			$this -> bot -> db -> query("INSERT INTO #___raid_points (id, points, raiding) VALUES (" . $this -> points_to($name) . ", 0, 1) ON DUPLICATE KEY UPDATE raiding = 1");

			//Update last_raid
			$query = "UPDATE #___users SET last_raid = " . time() . " WHERE nickname = '$name'";
			$this -> bot -> db -> query($query);
			$this -> user[$name] = $this -> bot -> core("chat") -> get_uid($name);
			$this -> bot -> send_output("", "##highlight##$name##end## has ##highlight##joined##end## the raid :: " . $this -> clickjoin(), "both");
			return "you have joined the Raid";
		}
		else
			return "No raid in progress";
	}



	/*
	Leaves a Raid
	*/
	function leave_raid($name)
	{
		if (!isset($this -> user[$name]))
			return "You are not in the raid.";
		else
		{
			unset($this -> user[$name]);
			$this -> bot -> db -> query("UPDATE #___raid_points SET raiding = 0 WHERE id = " . $this -> points_to($name));
			$this -> bot -> send_tell($name, "You have ##highlight##left##end## the raid.", 1);
			return "##highlight##$name##end## has ##highlight##left##end## the raid :: " . $this -> clickjoin();
		}
	}



	/*
	Kicks someone from the raid
	*/
	function kick_raid($name, $who)
	{
		if ($this -> bot -> core("security") -> check_access($name, $this -> bot -> core("settings") -> get('Raid', 'Command')))
		{
			if (!isset($this -> user[$who]))
				return "##highlight##$who##end## is not in the raid.";
			else
			{
				unset($this -> user[$who]);
				$this -> bot -> db -> query("UPDATE #___raid_points SET raiding = 0 WHERE id = " . $this -> points_to($who));
				$this -> bot -> send_tell($who, "##highlight##$name##end## kicked you from the raid.");
				return "##highlight##$who##end## was kicked from the raid.";
			}
		}
		else
			return "You must be a " . $this -> bot -> core("settings") -> get('Raid', 'Command') . " to do this";
	}




	/*
	Checks memebers on a raid
	*/
	function check_raid($name)
	{
		if ($this -> bot -> core("security") -> check_access($name, $this -> bot -> core("settings") -> get('Raid', 'Command')))
		{
			$players = array_keys($this -> user);
			sort($players);

			$inside = "##blob_title##:::: People in the raid ::::##end##\n\n";

			if (!empty($players))
			{
				if($this -> bot -> game == "ao")
				{
					foreach ($players as $player)
					{
						if (!empty($assist))
							$assist .= " \\n /assist $player";
						else
							$assist = "/assist $player";
					}

					$inside .= "<a href='chatcmd://$assist'>Check all raid members</a>\n\n";
				}
				foreach ($players as $player)
				{
					$inside .= $player . " [".$this -> bot -> core("tools") -> chatcmd(
					"raid kick ".$player, "Kick")."]\n";
				}
			}
			else
				$inside .= "There are no members of this raid.";

			return "Players in raid :: " .
			$this -> bot -> core("tools") -> make_blob("click to view", $inside);
		}
		else
			return "You must be a " . $this -> bot -> core("settings") -> get('Raid', 'Command') . " to do this";
	}


	/*
	Locks/unlocks a Raid
	*/
	function lock_raid($name, $lock)
	{
		if ($this -> bot -> core("security") -> check_access($name, $this -> bot -> core("settings") -> get('Raid', 'Command')))
		{
			if (strtolower($lock) == "lock")
			{
				$this -> locked = true;
				return "##highlight##$name##end## has ##highlight##locked##end## the raid.";
			}
			else
			{
				$this -> locked = false;
				return "##highlight##$name##end## has ##highlight##unlocked##end## the raid.";
			}
		}
		else
		return "You must be a " . $this -> bot -> core("settings") -> get('Raid', 'Command') . " to do this";
	}



	/*
	Make click to join blob
	*/
	function clickjoin()
	{
		$inside = "##blob_title##:::: Join/Leave Raid ::::##end##\n\n";
		if($this -> description && !empty($this -> description))
			$inside .= "Description:\n     ".$this -> description;
		$inside .= "\n\n - ".$this -> bot -> core("tools") -> chatcmd("raid join", "Join the raid")."\n";
		$inside .= " - ".$this -> bot -> core("tools") -> chatcmd("raid leave", "Leave the raid")."\n";

		return $this -> bot -> core("tools") -> make_blob("click to join", $inside);
	}
	/*
	Get correct char for points
	*/
	function points_to($name)
	{
		return $this -> bot -> core("points") -> points_to($name);
	}

	/*
	This gets called on cron
	*/
	function cron()
	{
		if(!$this -> paused)
		{
			$points = $this -> bot -> core("settings") -> get('Raid', 'Points');
			if(!is_numeric($points))
			{
				$this -> bot -> send_output("", "##error##Error: Invalid Amount set for Points in Settings (must be a number)", "both");
				$this -> pause(TRUE);
			}
			else
			{
				$this -> bot -> db -> query("UPDATE #___raid_points SET points = points + ".$points." WHERE raiding = 1");
			}
		}

		$this -> announce += 1;
		if ($this -> announce == 10)
		{
			$this -> bot -> send_output("", "Raid is running for ##highlight##" .
			(((int)((time () - $this -> start) / 60)) + 1) . "##end## minutes now.", "both");
			$this -> announce = 0;
		}
	}

	function pause($paused)
	{
		if($paused)
			$this -> bot -> send_output("", "Raid Point Ticker Paused", "both");
		else
			$this -> bot -> send_output("", "Raid Point Ticker Unpaused", "both");
		$this -> paused = $paused;
	}

}
?>