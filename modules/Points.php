<?php
/*
* Points.php - Handles raidpoints.
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2010 Thomas Juberg, ShadowRealm Creations and the BeBot development team.
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
		parent::__construct($bot, get_class($this));
		$this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("raid_points", "true") . "
				(id INT NOT NULL PRIMARY KEY,
				nickname VARCHAR(20),
				points decimal(11,2) default '0.00',
				raiding TINYINT DEFAULT '0',
				raidingas VARCHAR(20))");
		$this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("raid_points_log", "true") . "
				(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				name VARCHAR(20),
				points decimal(11,2) default '0.00',
				by_who VARCHAR(20),
				time INT,
				why VARCHAR(500))");
		$this->update_table();
		$this->bot->core("settings")->create("Points", "Transfer", FALSE, "Can points be transfered?");
		$this->bot->core("settings")->create("Points", "To_Main", FALSE, "Are points shared over all alts?");
		$this->help['description'] = 'Manage raid points';
		$this->help['command']['points [name]'] = "Shows the amount of points in [name]s account. If [name] is not given it shows the points in your account";
		$this->help['command']['points give <name> <points>'] = "Gives <points> points to player <name>";
		$this->help['command']['points add <name> <points> <why>'] = "Adds <points> points to player <name>s point account";
		$this->help['command']['points [del/rem] <name> <points> <why>'] = "Removes <points> points from player <name>s point account";
		$this->help['command']['points transfer <(on|off)>'] = "Turns ability to give points on or off.";
		$this->help['command']['points tomain <(on|off)>'] = "Turns ability to give points from alts to main on or off.";
		$this->help['command']['points all'] = "Shows the combined number of points on your main and alts.";
		$this->help['command']['points top'] = "Shows the 25 biggest point accounts.";
		$this->register_command("all", "points", "GUEST", array("add" => "SUPERADMIN" , "del" => "SUPERADMIN" , "rem" => "SUPERADMIN" , "transfer" => "SUPERADMIN" , "tomain" => "SUPERADMIN" , "all" => "SUPERADMIN"));
		$this->register_module("points");
	}

	function update_table()
	{
		if ($this->bot->db->get_version("raid_points") == 4)
		{
			return;
		}
		switch ($this->bot->db->get_version("raid_points"))
		{
			case 1:
				$fields = $this->bot->db->select("EXPLAIN #___raid_points", MYSQL_ASSOC);
				foreach ($fields as $field)
				{
					if ($field['Field'] == "points")
					{
						if ($field['Type'] == "int(11)")
							$updatepoints = TRUE;
					}
				}
				$this->bot->db->update_table("raid_points", "points", "modify", "ALTER IGNORE TABLE #___raid_points modify `points` decimal(11,2) default '0.00'");
				if ($updatepoints)
				{
					$this->bot->db->query("UPDATE #___raid_points SET points = points / 10");
				}
			case 2:
				$this->bot->db->update_table("raid_points", "nickname", "add", "ALTER IGNORE TABLE #___raid_points ADD nickname VARCHAR(20) DEFAULT '' after id");
				$users = $this->bot->db->select("SELECT id from #___raid_points");
				if (! empty($users))
				{
					foreach ($users as $id)
					{
						$nick = $this->bot->db->select("SELECT nickname from #___users WHERE id = " . $id[0]);
						if (empty($nick))
						{
							$nick = $this->bot->db->select("SELECT nickname from #___whois WHERE id = " . $id[0]);
							if (empty($nick))
							{
								//$getnames = TRUE;
							}
							else
							{
								$nick = $nick[0][0];
							}
						}
						else
						{
							$nick = $nick[0][0];
						}
						if ($nick)
						{
							$this->bot->db->query("UPDATE #___raid_points SET nickname = '$nick' WHERE id = " . $id[0]);
						}
					}
				}
			case 3:
				$this->bot->db->update_table("raid_points", "raidingas", "add", "ALTER IGNORE TABLE #___raid_points ADD raidingas VARCHAR(20) DEFAULT '' after raiding");
			default:
		}
		$this->bot->db->set_version("raid_points", 4);
	}

	/*
	This gets called on a tell with the command
	*/
	function command_handler($name, $msg, $origin)
	{
		$msg = explode(" ", $msg, 5);
		Switch (strtolower($msg[1]))
		{
			case 'give':
				$this->give_points($name, $msg[2], $info[3]);
				Break;
			case 'add':
				if (strlen($msg[4]) < 5)
					Return ("Error: Reason required, min ##highlight##5##end## letters");
				$this->add_points($name, $msg[2], $msg[3], $msg[4]);
				Break;
			case 'del':
			case 'rem':
				if (strlen($msg[4]) < 5)
					Return ("Error: Reason required, min ##highlight##5##end## letters");
				$this->rem_points($name, $msg[2], $msg[3], $msg[4]);
				Break;
			case 'transfer':
				$this->transfer_points($name, $msg[2]);
				Break;
			case 'tomain':
				$this->tomain_points($name, $msg[2]);
				Break;
			case 'all':
				$this->all_points($name);
				Break;
			case 'top':
				$this->top_points($name);
				Break;
			case 'log':
			case 'logs':
				Return $this->view_log($name, $msg[2], $msg[3]);
			case '':
				$this->show_points($name, false);
				Break;
			Default:
				$this->show_points($name, $msg[1]);
		}
		Return FALSE;
	}

	/*
	Shows your points
	*/
	function show_points($name, $target)
	{
		if (! $target || strtolower($target) == strtolower($name))
		{
			$result = $this->bot->db->select("SELECT points, nickname FROM #___raid_points WHERE id = " . $this->points_to($name));
			if ($result)
			{
				if ($result[0][1] == "")
				{
					$this->bot->db->query("UPDATE #___raid_points SET nickname = '" . $this->points_to_name($name) . "' WHERE id = " . $this->points_to($name));
				}
				$points = $this->round($result[0][0]);
			}
			else
			{
				$points = 0;
			}
			$this->bot->send_tell($name, "You have ##highlight##$points##end## raidpoints.");
		}
		else
		{
			if ($this->bot->core("security")->check_access($name, "admin"))
			{
				if (! $this->bot->core('player')->id($target))
				{
					$this->bot->send_tell($name, "Player ##highlight##$target##end## does not exist.");
				}
				else
				{
					$result = $this->bot->db->select("SELECT points, nickname FROM #___raid_points WHERE id = " . $this->points_to($target));
					if ($result)
					{
						if ($result[0][1] == "")
						{
							$this->bot->db->query("UPDATE #___raid_points SET nickname = '" . $this->points_to_name($target) . "' WHERE id = " . $this->points_to($target));
						}
						$points = $this->round($result[0][0]);
					}
					else
					{
						$points = 0;
					}
					$this->bot->send_tell($name, "Player " . $target . " has ##highlight##$points##end## raidpoints.");
				}
			}
			else
			{
				$this->bot->send_tell($name, "You must be an admin to view others points");
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
		$this->bot->send_tell($name, "Fetching full list of points, this might take a while.");
		$result = $this->bot->db->select("SELECT nickname, points FROM #___raid_points WHERE points > 0 ORDER BY points DESC");
		$inside = "##blob_title##:::: All raidpoints ::::##end####blob_text##\n\n";
		if (! empty($result))
		{
			foreach ($result as $val)
			{
				$space = "                    ";
				$nl = "-" . strlen($val[0]);
				$nl = round($nl * 1.5);
				$space = substr($space, 0, $nl);
				$val[1] = $this->round($val[1]);
				$inside .= "##highlight##" . $val[0] . "##end##$space - ##highlight##" . ($val[1]) . "##end##\n";
			}
		}
		$this->bot->send_tell($name, "All raidpoints :: " . $this->bot->core("tools")->make_blob("click to view", $inside));
		//	}
	//	else
	//	$this -> bot -> send_tell($name, "You must be a superadmin to do this");
	}

	/*
	Shows top25 points
	*/
	function top_points($name)
	{
		$result = $this->bot->db->select("SELECT nickname, points FROM #___raid_points WHERE points > 0 ORDER BY points DESC LIMIT 25");
		if (! empty($result))
		{
			$inside = "##blob_title##:::: Top 25 raidpoints ::::##end####blob_text##\n\n";
			$num = 1;
			foreach ($result as $val)
			{
				$space = "                    ";
				$nl = "-" . strlen($val[0]);
				$nl = round($nl * 1.5);
				$space = substr($space, 0, $nl);
				$val[1] = $this->round($val[1]);
				$inside .= $num . ". ##highlight##" . $val[0] . "##end##$space - ##highlight##" . ($val[1]) . "##end##\n";
				$num ++;
			}
			$this->bot->send_tell($name, "Top 25 raidpoints :: " . $this->bot->core("tools")->make_blob("click to view", $inside));
		}
		else
		{
			$this->bot->send_tell($name, "Im sorry but there appears to be no one with raidpoints yet");
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
		if ($toggle == "check")
		{
			if (! $this->bot->core("settings")->get("Points", "To_main"))
			{
				Return ("ToMain is Off No Check Required");
			}
			else
			{
				$check = TRUE;
			}
		}
		else
		{
			$stat = FALSE;
			$txt = "disabled";
		}
		if (! $check)
			$this->bot->core("settings")->save("Points", "To_main", $stat);
		$add = "";
		//if ($stat || $check)
		{
			$result = $this->bot->db->select("SELECT id, nickname, points FROM #___raid_points WHERE points > 0");
			foreach ($result as $res)
			{
				if ($res[0] != $this->points_to($res[1]))
				{
					$this->bot->db->query("UPDATE #___raid_points SET points = 0 WHERE id = " . $res[0]);
					$resu = $this->bot->db->select("SELECT nickname, points FROM #___raid_points WHERE id = " . $this->points_to($res[1]));
					if (empty($resu))
						$this->bot->db->query("INSERT INTO #___raid_points (id, nickname, points, raiding) VALUES (" . $this->points_to($res[1]) . ", '" . $this->points_to_name($res[1]) . "', " . $res[2] . ", 0)");
					else
						$this->bot->db->query("UPDATE #___raid_points SET points = " . ($res[2] + $resu[0][1]) . " WHERE id = " . $this->points_to($res[0]));
				}
			}
			$add = " All points have been transfered.";
		}
		if ($check)
			$this->bot->send_tell($name, $add);
		else
			$this->bot->send_tell($name, "Points going to the main character's account is now ##highlight##" . $txt . "##end##." . $add);
		//}
	//else
	//$this -> bot -> send_tell($name, "You must be a superadmin to do this");
	}

	function check_alts($main)
	{
		$alts = $this->bot->core("alts")->get_alts($main);
		if (! empty($alts))
		{
			foreach ($alts as $alt)
			{
				$result = $this->bot->db->select("SELECT id, nickname, points FROM #___raid_points WHERE points != 0 AND id = " . $this->points_to($alt, FALSE));
				if (! empty($result))
				{
					$res = $result[0];
					if ($res[0] != $this->points_to($res[1]))
					{
						$resu = $this->bot->db->select("SELECT nickname, points FROM #___raid_points WHERE id = " . $this->points_to($res[1]));
						if (empty($resu))
							$this->bot->db->query("INSERT INTO #___raid_points (id, nickname, points, raiding) VALUES (" . $this->points_to($res[1]) . ", '" . $this->points_to_name($res[1]) . "', " . $res[2] . ", 0)");
						else
							$this->bot->db->query("UPDATE #___raid_points SET points = " . ($res[2] + $resu[0][1]) . " WHERE id = " . $this->points_to($res[0]));
						$check = $this->bot->db->select("SELECT nickname, points FROM #___raid_points WHERE id = " . $this->points_to($res[1]));
						if (! empty($check) && ($check[0][1] != ($res[2] + $resu[0][1])))
						{
							echo "Error With Transfering Points from Alt $alt to $main";
						}
						else
						{
							$this->bot->db->query("UPDATE #___raid_points SET points = 0 WHERE id = " . $res[0]);
						}
					}
				}
				$result = $this->bot->db->select("SELECT id FROM #___raid_points WHERE raiding = 1 and id = " . $this->points_to($alt, FALSE));
				if (! empty($result))
				{
					$res = $result[0];
					$this->bot->db->query("UPDATE #___raid_points SET raiding = 0 WHERE id = " . $res[0]);
					$this->bot->db->query("UPDATE #___raid_points SET raiding = 1 WHERE id = " . $this->points_to($alt));
				}
			}
		}
	}

	/*
	Enable/Disable !points give
	*/
	function transfer_points($name, $toggle)
	{
		if ($this->bot->core("security")->check_access($name, "superadmin"))
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
			$this->bot->core("settings")->save("Points", "Transfer", $stat);
			$this->bot->send_tell($name, "Transfering points has been ##highlight##" . $txt . "##end##.");
		}
		else
			$this->bot->send_tell($name, "You must be a superadmin to do this");
	}

	/*
	Transfers points
	*/
	function give_points($name, $who, $num)
	{
		if ($this->bot->core("settings")->get("Points", "Transfer"))
		{
			if (! is_numeric($num))
			{
				$this->bot->send_tell($name, "$num is not a valid points value.");
				return;
			}
			$result = $this->bot->db->select("SELECT points FROM #___raid_points WHERE id = " . $this->points_to($name));
			if (! $result)
			{
				$this->bot->send_tell($name, "You have no points.");
				return;
			}
			if ($num > ($result[0][0]))
			{
				$this->bot->send_tell($name, "You only have ##highlight''" . ($result[0][0]) . "##end## raid points.");
				return;
			}
			else if (! $this->bot->core('player')->id($who))
			{
				$this->bot->send_tell($name, "Player ##highlight##$who##end## does not exist.");
				return;
			}
			else
			{
				$this->bot->db->query("UPDATE #___raid_points SET points = points - " . ($num) . " WHERE id = " . $this->points_to($name));
				$this->bot->db->query("INSERT INTO #___raid_points (id, nickname, points) VALUES (" . $this->points_to($who) . ", '" . $this->points_to_name($who) . "', $num) ON DUPLICATE KEY UPDATE points = points + VALUES(points)");
				$this->bot->send_tell($name, "You gave ##highlight##$num##end## raidpoints to ##highlight##$who##end##.");
				$this->bot->send_tell($who, "You got ##highlight##$num##end## raidpoints from ##highlight##$name##end##.");
				return;
			}
		}
		else
		{
			$this->bot->send_tell($name, "Transfering points has been ##highlight##disabled##end##.");
		}
	}

	/*
	Adds points
	*/
	function add_points($name, $who, $num, $why, $silent = FALSE)
	{
		if (! is_numeric($num))
		{
			$this->bot->send_tell($name, "$num is not a valid points value.");
			return FALSE;
		}
		if (! $this->bot->core('player')->id($who))
		{
			$this->bot->send_tell($name, "Player ##highlight##$who##end## does not exist.");
			return FALSE;
		}
		else
		{
			$this->bot->db->query("INSERT INTO #___raid_points (id, nickname, points) VALUES (" . $this->points_to($who) . ", '" . $this->points_to_name($who) . "', $num) ON DUPLICATE KEY UPDATE points = points + VALUES(points)");
			if (! $silent)
			{
				$this->bot->send_output("", "##highlight##$name##end## added ##highlight##$num##end## raidpoints to ##highlight##$who##end##'s account.", "both");
				$this->bot->send_tell($name, "You added ##highlight##$num##end## raidpoints to ##highlight##$who##end##'s account.");
				$this->bot->send_tell($who, "##highlight##$name##end## added ##highlight##$num##end## raidpoints to your account.($why)");
			}
			$this->log($name, $who, $num, $why);
			return TRUE;
		}
	}

	/*
	Remove points
	*/
	function rem_points($name, $who, $num, $why, $silent = FALSE)
	{
		if (! is_numeric($num))
		{
			$this->bot->send_tell($name, "$num is not a valid points value.");
			return FALSE;
		}
		if (! $this->bot->core('player')->id($who))
		{
			$this->bot->send_tell($name, "Player ##highlight##$who##end## does not exist.");
			return FALSE;
		}
		else
		{
			$this->bot->db->query("UPDATE #___raid_points SET points = points - " . ($num) . " WHERE id = " . $this->points_to($who));
			if (! $silent)
			{
				$this->bot->send_output("", "##highlight##$name##end## removed ##highlight##$num##end## raidpoints from ##highlight##$who##end##'s account.", "both");
				$this->bot->send_tell($name, "You removed ##highlight##$num##end## raidpoints from ##highlight##$who##end##'s account.");
				$this->bot->send_tell($who, "##highlight##$name##end## removed ##highlight##$num##end## raidpoints from your account. ($why)");
			}
			$this->log($name, $who, "-" . $num, $why);
			return TRUE;
		}
	}

	/*
	Get correct char for points
	*/
	function points_to($name, $tomain = TRUE)
	{
		if (! $tomain || ! $this->bot->core("settings")->get("Points", "To_main"))
			return $this->bot->core('player')->id($name);
		$main = $this->bot->core("alts")->main($name);
		return $this->bot->core('player')->id($main);
	}

	function points_to_name($name, $tomain = TRUE)
	{
		if (! $tomain || ! $this->bot->core("settings")->get("Points", "To_main"))
			return $name;
		return $this->bot->core("alts")->main($name);
	}

	function log($name, $who, $num, $why)
	{
		$name = ucfirst(strtolower($name));
		$who = ucfirst(strtolower($who));
		$this->bot->db->query("INSERT INTO #___raid_points_log (name, points, by_who, time, why) VALUES ('$who', $num, '$name', " . time() . ", '" . mysql_real_escape_string($why) . "')");
	}

	function view_log($name, $timeorname, $time2)
	{
		if (! $timeorname)
			$timeorname = $name;
		if (! is_numeric($timeorname))
		{
			$main = $this->bot->core("alts")->main($timeorname);
			$alts = $this->bot->core("alts")->get_alts($main);
			if (strtolower($main) == strtolower($name))
				$ownlogs = TRUE;
			else
			{
				if (! empty($alts))
				{
					foreach ($alts as $alt)
					{
						if (strtolower($alt) == strtolower($name))
							$ownlogs = TRUE;
					}
				}
			}
		}
		if ($ownlogs || $this->bot->core("security")->check_access($name, "superadmin"))
		{
			if (is_numeric($timeorname))
			{
				return ("point logs view by time disabled");
				//if(!empty($time2))
				//{
				//	if(!is_numeric($time2))
				//		Return("Error: 2nd time
				//}
				$field = "time";
				$value = "> " . time() - ($timeorname * 60 * 60);
				$for = "last $timeorname hours";
			}
			else
			{
				$field = "name";
				$value = "= '$main'";
				if (! empty($alts))
				{
					foreach ($alts as $alt)
						$value .= " OR $field = '$alt'";
				}
				$for = "$main and his alts";
			}
			$logs = $this->bot->db->select("SELECT name, points, by_who, time, why FROM #___raid_points_log WHERE $field " . $value . " ORDER BY time DESC, id");
			if (! empty($logs))
			{
				$inside = " :: Logs for $for ::##seablue##";
				foreach ($logs as $log)
				{
					if ($log[1] >= 0)
						$color = "green";
					else
						$color = "red";
					$inside .= "\n\n" . gmdate($this->bot->core("settings")->get("Time", "FormatString"), $log[3]) . " GMT";
					$inside .= "\n##highlight##" . $log[0] . "##end##: ##$color##" . $log[1] . "##end## points by ##highlight##" . $log[2] . "##end## (" . $log[4] . ")";
				}
				return ("Logs for $for :: " . $this->bot->core("tools")->make_blob("click to view", $inside));
			}
			else
				Return ("No logs Found for $for");
		}
		else
			Return ("You must be an ##highlight##superadmin##end## to view others point logs");
	}

	function round($num)
	{
		$num2 = explode(".", $num, 2);
		if ($num2[1] == "00")
			$num = $num2[0];
		return $num;
	}
}
?>