<?php
/*
* Points.php - Handles raidpoints.
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
* File last changed at $LastChangedDate: 2009-01-01 02:45:16 +0100 (Thu, 01 Jan 2009) $
* Revision: $Id: Points.php 1941 2009-01-01 01:45:16Z temar $
*/


$points = new Points($bot);

/*
The Class itself...
*/
class Points extends BaseActiveModule
{
	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS " . $this -> bot -> db -> define_tablename("raid_points", "true") . "
				(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				points decimal(11,2) default '0.00',
				raiding TINYINT DEFAULT '0')");

		$this -> update_table();

		$this -> bot -> core("settings") -> create("Points", "Transfer", FALSE, "Can points be transfered?");
		$this -> bot -> core("settings") -> create("Points", "To_Main", FALSE, "Are points shared over all alts?");

		$this -> help['description'] = 'Manage raid points';
		$this -> help['command']['points [name]']="Shows the amount of points in [name]s account. If [name] is not given it shows the points in your account";
		$this -> help['command']['points give <name> <points>']="Gives <points> points to player <name>";
		$this -> help['command']['points add <name> <points>'] = "Adds <points> points to player <name>s point account";
		$this -> help['command']['points del <name> <points>'] = "Removes <points> points from player <name>s point account";
		$this -> help['command']['points transfer <(on|off)>'] = "Turns ability to give points on or off.";
		$this -> help['command']['points tomain <(on|off)>'] = "Turns ability to give points from alts to main on or off.";
		$this -> help['command']['points all'] = "Shows the combined number of points on your main and alts.";
		$this -> help['command']['points top'] = "Shows the 25 biggest point accounts.";

		$this -> register_command("all", "points", "GUEST", array("add" => "SUPERADMIN", "del" => "SUPERADMIN", "rem" => "SUPERADMIN", "transfer" => "SUPERADMIN", "tomain" => "SUPERADMIN", "all" => "SUPERADMIN"));
		$this -> register_module("points");
	}

	function update_table()
	{
		if ($this -> bot -> db -> get_version("raid_points") == 2)
		{
			return;
		}

		switch ($this -> bot -> db -> get_version("raid_points"))
		{
			case 1:
				$fields = $this -> bot -> db -> select("EXPLAIN #___raid_points", MYSQL_ASSOC);
				foreach($fields as $field)
				{
					if($field['Field'] == "points")
					{
						if($field['Type'] == "int(11)")
							$updatepoints = TRUE;
					}
				}
				$this -> bot -> db -> update_table("raid_points", "points", "modify",
					"ALTER IGNORE TABLE #___raid_points modify `points` decimal(11,2) default '0.00'");
				if($updatepoints)
				{
					$this -> bot -> db -> query("UPDATE #___raid_points SET points = points / 10");
				}
			case 2:
			default:
		}
		$this -> bot -> db -> set_version("raid_points", 2);
	}

	/*
	This gets called on a tell with the command
	*/
	function command_handler($name, $msg, $origin)
	{
		$msg = explode(" ", $msg, 4);
		Switch($msg[1])
		{
			case 'give':
				$this -> give_points($name, $msg[2], $info[3]);
				Break;
			case 'add':
				$this -> add_points($name, $msg[2], $msg[3]);
				Break;
			case 'del':
			case 'rem':
				$this -> rem_points($name, $msg[2], $msg[3]);
				Break;
			case 'transfer':
				$this -> transfer_points($name, $msg[2]);
				Break;
			case 'tomain':
				$this -> tomain_points($name, $msg[2]);
				Break;
			case 'all':
				$this -> all_points($name);
				Break;
			case 'top':
				$this -> top_points($name);
				Break;
			case '':
				$this -> show_points($name, false);
				Break;
			Default:
				$this -> show_points($name, $msg[1]);
		}
		Return FALSE;
	}


	/*
	Shows your points
	*/
	function show_points($name, $target)
	{
		if (!$target)
		{
			$result = $this -> bot -> db -> select("SELECT points FROM #___raid_points WHERE id = " . $this -> points_to($name));
			if ($result)
			{
				$points = $result[0][0];
			}
			else
			{
				$points = 0;
			}

			$this -> bot -> send_tell($name, "You have <font color=#ffff00>$points##end## raidpoints.");
		}
		else
		{
			if ($this -> bot -> core("security") -> check_access($name, "admin"))
			{
				if (!$this -> bot -> core("chat") -> get_uid($target))
				{
					$this -> bot -> send_tell ($name, "Player <font color=#ffff00>$target##end## does not exist.");
				}
				else
				{
					$result = $this -> bot -> db -> select("SELECT points FROM #___raid_points WHERE id = " . $this -> points_to($target));
					if ($result)
					{
						$points = $result[0][0];
					}
					else
					{
						$points = 0;
					}
					$this -> bot -> send_tell($name, "Player " . $target . " has <font color=#ffff00>$points##end## raidpoints.");
				}
			}
			else
			{
				$this -> bot -> send_tell($name, "You must be an admin to view others points");
			}

		}
	}


	/*
	Shows your points
	*/
	function all_points($name)
	{
	//	if ($this -> bot -> core("security") -> check_access($name, "superadmin"))
	//	{
			$this -> bot -> send_tell($name, "Fetching full list of points, this might take a while.");
			$result = $this -> bot -> db -> select("SELECT nickname, points FROM #___raid_points, #___users WHERE #___raid_points.id = #___users.char_id AND NOT points = 0 ORDER BY points DESC");
			$inside = "##blob_title##:::: All raidpoints ::::##end####blob_text##\n\n";
			if (!empty($result))
			{
				foreach ($result as $val)
				{
					$inside .= $val[0] . " ##blob_text##" . ($val[1]) . "##end##\n";
				}
			}

			$this -> bot -> send_tell($name, "All raidpoints :: " . $this -> bot -> core("tools") -> make_blob("click to view", $inside));
	//	}
	//	else
	//	$this -> bot -> send_tell($name, "You must be a superadmin to do this");
	}


	/*
	Shows top25 points
	*/
	function top_points($name)
	{
		$result = $this -> bot -> db -> select("SELECT nickname, points FROM #___raid_points, #___users WHERE #___raid_points.id = #___users.char_id AND NOT points = 0 ORDER BY points DESC LIMIT 0,25");
		if (!empty($result))
		{
			$inside = "##blob_title##:::: Top 25 raidpoints ::::##end####blob_text##\n\n";
			$num = 1;
			foreach ($result as $val)
			{
				$inside .= "##blob_text##" . $num . ".##end## " . $val[0] . " ##blob_text##" . ($val[1]) . "##end##\n";
				$num++;
			}
			$this -> bot -> send_tell($name, "Top 25 raidpoints :: " . $this -> bot -> core("tools") -> make_blob("click to view", $inside));
		}
		else
		{
			$this -> bot -> send_tell($name, "Im sorry but there appears to be no one with raidpoints yet");
		}
	}


	/*
	Use main char's account for points...
	*/
	function tomain_points($name, $toggle)
	{
		//if ($this -> bot -> core("security") -> check_access($name, "superadmin"))
		//{
			$toggle == strtolower($toggle);
			if ($toggle == "on")
			{
				$stat = TRUE;
				$txt = "enabled";
			}
			else
			{
				$stat = FALSE;
				$txt = "disabled";
			}

			$this -> bot -> core("settings") -> save("Points", "To_main", $stat);

			$add = "";

			if ($stat)
			{
				$result = $this -> bot -> db -> select("SELECT id, points FROM #___raid_points WHERE points != 0");
				foreach ($result as $res)
				{
					if ($res[0] != $this -> points_to($res[0]))
					{
						$this -> bot -> db -> query("UPDATE #___raid_points SET points = 0 WHERE id = " . $res[0]);
						$resu = $this -> bot -> db -> select("SELECT points FROM #___raid_points WHERE id = " . $this -> points_to($res[0]));
						if (empty($resu))
						$this -> bot -> db -> query("INSERT INTO #___raid_points (id, points, raiding) VALUES (" . $this -> points_to($res[0]) . ", " . $res[1] . ", 0)");
						else
						$this -> bot -> db -> query("UPDATE #___raid_points SET points = " . ($res[1] + $resu[0][0]) . " WHERE id = " . $this -> points_to($res[0]));
					}
				}
				$add = " All points have been transfered.";
			}

			$this -> bot -> send_tell($name, "Points going to the main character's account is now <font color=#ffff00>" .
			$txt . "##end##." . $add);
		//}
		//else
		//$this -> bot -> send_tell($name, "You must be a superadmin to do this");
	}


	/*
	Enable/Disable !points give
	*/
	function transfer_points($name, $toggle)
	{
		if ($this -> bot -> core("security") -> check_access($name, "superadmin"))
		{
			$toggle == strtolower($toggle);
			if ($toggle == "on")
			{
				$stat = TRUE;
				$txt = "enabled";
			}
			else
			{
				$stat = FALSE;
				$txt = "disabled";
			}

			$this -> bot -> core("settings") -> save("Points", "Transfer", $stat);

			$this -> bot -> send_tell($name, "Transfering points has been <font color=#ffff00>" .
			$txt . "##end##.");
		}
		else
			$this -> bot -> send_tell($name, "You must be a superadmin to do this");
	}


	/*
	Transfers points
	*/
	function give_points($name, $who, $num)
	{
		if ($this -> bot -> core("settings") -> get("Points", "Transfer"))
		{
			if (!is_numeric($num))
			{
				$this -> bot -> send_tell ($name, "$num is not a valid points value.");
				return;
			}

			$result = $this -> bot -> db -> select("SELECT points FROM #___raid_points WHERE id = " . $this -> points_to($name));
			if (!$result)
			{
				$this -> bot -> send_tell ($name, "You have no points.");
				return;
			}

			if ($num > ($result[0][0]))
			{
				$this -> bot -> send_tell ($name, "You only have <font color=#ffff00>" . ($result[0][0]) . "##end## raid points.");
				return;
			}

			else if (!$this -> bot -> core("chat") -> get_uid($who))
			{
				$this -> bot -> send_tell ($name, "Player <font color=#ffff00>$who##end## does not exist.");
				return;
			}
			else
			{
				$this -> bot -> db -> query("UPDATE #___raid_points SET points = points - " . ($num) .
				" WHERE id = " . $this -> points_to($name));
				$this -> bot -> db -> query("INSERT INTO #___raid_points (id, points) VALUES (" . $this -> points_to($who) . ", $num) ON DUPLICATE KEY UPDATE points = points + VALUES(points)");
				$this -> bot -> send_tell($name, "You gave <font color=#ffff00>$num##end## raidpoints to <font color=#ffff00>$who##end##.");
				$this -> bot -> send_tell($who, "You got <font color=#ffff00>$num##end## raidpoints from <font color=#ffff00>$name##end##.");
				return;
			}
		}
		else
		{
			$this -> bot -> send_tell($name, "Transfering points has been <font color=#ffff00>disabled##end##.");
		}
	}


	/*
	Adds points
	*/
	function add_points($name, $who, $num)
	{
	//	if ($this -> bot -> core("security") -> check_access($name, "superadmin"))
	//	{
			if (!is_numeric($num))
			{
				$this -> bot -> send_tell ($name, "$num is not a valid points value.");
				return;
			}

			if (!$this -> bot -> core("chat") -> get_uid($who))
			{
				$this -> bot -> send_tell ($name, "Player <font color=#ffff00>$who##end## does not exist.");
				return;
			}
			else
			{
				$this -> bot -> db -> query("INSERT INTO #___raid_points (id, points) VALUES (" . $this -> points_to($who) . ", $num) ON DUPLICATE KEY UPDATE points = points + VALUES(points)");
				$this -> bot -> send_output("", "<font color=#ffff00>$name##end## added <font color=#ffff00>$num##end## raidpoints to <font color=#ffff00>$who##end##'s account.", "both");
				$this -> bot -> send_tell($name, "You added <font color=#ffff00>$num##end## raidpoints to <font color=#ffff00>$who##end##'s account.");
				$this -> bot -> send_tell($who, "<font color=#ffff00>$name##end## added <font color=#ffff00>$num##end## raidpoints to your account.");
				return;
			}
	//	}
	//	else
	//	{
	//		$this -> bot -> send_tell($name, "You must be a superadmin to do this");
	//		return;
	//	}
	}


	/*
	Remove points
	*/
	function rem_points($name, $who, $num)
	{
		//if ($this -> bot -> core("security") -> check_access($name, "superadmin"))
		//{
			if (!is_numeric($num))
			{
				$this -> bot -> send_tell ($name, "$num is not a valid points value.");
				return;
			}

			if (!$this -> bot -> core("chat") -> get_uid($who))
			{
				$this -> bot -> send_tell ($name, "Player <font color=#ffff00>$who##end## does not exist.");
				return;
			}
			else
			{
				$this -> bot -> db -> query("UPDATE #___raid_points SET points = points - " . ($num) .
				" WHERE id = " . $this -> points_to($who));
				$this -> bot -> send_output("", "<font color=#ffff00>$name##end## removed <font color=#ffff00>$num##end## raidpoints from <font color=#ffff00>$who##end##'s account.", "both");
				$this -> bot -> send_tell($name, "You removed <font color=#ffff00>$num##end## raidpoints from <font color=#ffff00>$who##end##'s account.");
				$this -> bot -> send_tell($who, "<font color=#ffff00>$name##end## removed <font color=#ffff00>$num##end## raidpoints from your account.");
				return;
			}
		//}
		//else
		//{
		//	$this -> bot -> send_tell($name, "You must be a superadmin to do this");
		//	return;
		//}
	}


	/*
	Get correct char for points
	*/
	function points_to($name)
	{
		if (!$this -> bot -> core("settings") -> get("Points", "To_main"))
			return $this -> bot -> core("chat") -> get_uid($name);

		$main = $this -> bot -> core("alts") -> main($name);
		return $this -> bot -> core("chat") -> get_uid($main);
	}
}
?>