<?php
/*
* Raffle.php - Module that handles raffles.
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
* Revision: $Id: Raffle.php 1833 2008-11-30 22:09:06Z alreadythere $
*/

/*
Add a "_" at the beginning of the file (_ClassName.php) if you do not want it to be loaded.
*/

$raffle = new Raffle($bot);



/*
The Class itself...
*/
class Raffle extends BaseActiveModule
{
	var $item;
	var $item_blank;
	var $users;
	var $output;
	var $admin;
	var $result;



	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> output = "group";
		$this -> result = "";

		$this -> register_command("all", "raffle", "GUEST");

		$this -> help['description'] = 'Module to handle item lotteries';
		$this -> help['command']['raffle start <item>']="Starts a raffle for item <item>.";
		$this -> help['command']['raffle reannounce']="Announces the current raffle again.";
		$this -> help['command']['raffle cancel']="Cancels the current raffle.";
		$this -> help['command']['raffle closing']="Close the current raffle.";
		$this -> help['command']['raffle result']="Announce the winner(s) of the last raffle.";
		$this -> help['command']['raffle output <(group|guild|both)>']="Chooses which channel raffles should be announced to.";
		$this -> help['command']['raffle join']="Joins the current raffle.";
		$this -> help['command']['raffle leave']="Leaves the current raffle.";
		$this -> help['notes'] = "Notes for the help goes in here.";

	}



	function command_handler($name, $msg, $origin)
	{
		if (preg_match("/^raffle start (.+)/i", $msg, $info))
		$this -> raffle_start($name, $info[1]);
		else if (preg_match("/^raffle join/i", $msg, $info))
		$this -> raffle_join($name);
		else if (preg_match("/^raffle leave/i", $msg, $info))
		$this -> raffle_leave($name);
		else if (preg_match("/^raffle output (group|guild|both)$/i", $msg, $info))
		$this -> raffle_output($name, $info[1]);
		else if (preg_match("/^raffle cancel$/i", $msg, $info))
		$this -> raffle_cancel($name);
		else if (preg_match("/^raffle reannounce$/i", $msg, $info))
		$this -> raffle_reannounce($name);
		else if (preg_match("/^raffle closing$/i", $msg, $info))
		$this -> raffle_closing($name);
		else if (preg_match("/^raffle result$/i", $msg, $info))
		$this -> raffle_result($name);
		else if (preg_match("/^raffle admin$/i", $msg, $info))
		$this -> bot -> send_tell($name, $this -> make_admin($name));
	}



	/*
	End raffle
	*/
	function raffle_result($name)
	{
		if (!empty($this -> item) && !empty($this -> users) && (($this -> admin == $name) ||
		$this -> bot -> core("security") -> check_access($name, "admin")))
		{
			$users = array_keys($this -> users);

			$usr_num = count($users);
			$max = 10000 - $usr_num;

			for ($i = 0; $i < $max; $i++)
			{
				$this -> users[$users[rand(0, ($usr_num - 1))]] += 1;
			}

			natsort($this -> users);

			$results = "<font color=CCInfoHeadline>::::: Raffle Results :::::</font><font color=CCInfoText>\n\n";
			$results .= "<font color=CCCCTextColor>" . $this -> admin . "</font> raffled <font color=CCCCTextColor>" . $this -> item_blank;
			$results .= "</font>. I rolled 10000 times. The results where:\n\n";

			$winner = "";
			$res = "";
			$count = count($this -> users);

			foreach ($this -> users as $key => $points)
			{
				if ($count == 1)
				$winner = $key;

				$res = "<font color=CCCCTextColor>". $count . ".</font> $key <font color=CCCCTextColor>" . $points . " points</font>\n" . $res;
				$count--;
			}

			$results .= $res;

			$this -> output("\n<font color=#ffff00>--------------------------------------------------------</font>\n" .
			"  <font color=#ffff00>" . $winner . "</font> has won the raffle for <font color=#ffff00>" .
			$this -> item . "</font>! :: " . $this -> bot -> core("tools") -> make_blob("view results", $results) . "\n" .
			"<font color=#ffff00>----------------------------------------------------------</font>");

			$this -> users = "";
			$this -> item = "";
			$this -> admin = "";
			$this -> item_blank = "";
			$this -> result = "Results from the last raffle: " . $this -> bot -> core("tools") -> make_blob("view results", $results);
		}
		else if (empty($this -> item))
		$this -> bot -> send_tell($name, "There is no raffle currently running.\n" . $this -> result);
		else if (empty($this -> users))
		$this -> bot -> send_tell($name, "Noone is in the raffle yet.");
		else
		$this -> bot -> send_tell($name, "You did not start the raffle nore are you bot administrator.");
	}



	/*
	Cancel raffle
	*/
	function raffle_cancel($name)
	{
		if (!empty($this -> item) && (($this -> admin == $name) ||
		$this -> bot -> core("security") -> check_access($name, "admin")))
		{

			$this -> output("<font color=#ffff00>$name</font> has canceled the raffle.");

			$this -> users = "";
			$this -> item = "";
			$this -> admin = "";
			$this -> item_blank = "";
		}
		else if (empty($this -> item))
		$this -> bot -> send_tell($name, "There is no raffle currently running.\n" . $this -> result);
		else
		$this -> bot -> send_tell($name, "You did not start the raffle nore are you bot administrator.");
	}



	/*
	Reannounces raffle
	*/
	function raffle_reannounce($name)
	{
		if (isset($this -> item) &&(($this -> admin == $name) || $this -> bot -> core("security") -> check_access($name, "admin")))
		{
			$this -> output("\n<font color=#ffff00>--------------------------------------------------------</font>\n" .
			"  The raffle for <font color=#ffff00>" . $this -> item . "</font> is still running :: " .
			$this -> click_join("join") . "\n" .
			"<font color=#ffff00>----------------------------------------------------------</font>");
		}
		else if (!isset($this -> item))
		$this -> bot -> send_tell($name, "There is no raffle currently running.");
		else
		$this -> bot -> send_tell($name, "You did not start the raffle nore are you bot administrator.");
	}



	/*
	Reannounces raffle
	*/
	function raffle_closing($name)
	{
		if (isset($this -> item) &&(($this -> admin == $name) || $this -> bot -> core("security") -> check_access($name, "admin")))
		{
			$this -> output("\n<font color=#ffff00>----------------------------------------------------------</font>\n" .
			"  The raffle for <font color=#ffff00>" . $this -> item . "</font> wil be " .
			"<font color=#ffff00>closing soon</font> :: " .
			$this -> click_join("join") . "\n" .
			"<font color=#ffff00>----------------------------------------------------------</font>");
		}
		else if (!isset($this -> item))
		$this -> bot -> send_tell($name, "There is no raffle currently running.");
		else
		$this -> bot -> send_tell($name, "You did not start the raffle nore are you bot administrator.");
	}



	/*
	Change output chan
	*/
	function raffle_output($name, $chan)
	{
		if (($this -> admin == $name) || $this -> bot -> core("security") -> check_access($name, "admin"))
		{
			$this -> output = $chan;
			$this -> output("Raffle output set to <font color=#ffff00>" . $chan . ".");
		}
		else
		$this -> bot -> send_tell($name, "You did not start the raffle nore are you bot administrator.");
	}



	/*
	Join the raffle
	*/
	function raffle_join($name)
	{
		if (empty($this -> item))
		$this -> bot -> send_tell($name, "There is no raffle running at the moment.");
		else if (isset($this -> users[$name]))
		$this -> bot -> send_tell($name, "You are already in the raffle.");
		else
		{
			$this -> users[$name] = 1;
			$this -> bot -> send_tell($name, "You have joined the raffle. " . $this -> click_join("leave"), 1);
			$this -> output("<font color=#ffff00>" . $name . "</font> has <font color=#ffff00>joined</font>" .
			" the raffle ::" . $this -> click_join("join"), 1);
		}
	}



	/*
	Leave the raffle
	*/
	function raffle_leave($name)
	{
		if (!isset($this -> item))
		$this -> bot -> send_tell($name, "There is no raffle running at the moment.");
		else if (!isset($this -> users[$name]))
		$this -> bot -> send_tell($name, "You are not in the raffle.");
		else
		{
			unset($this -> users[$name]);
			$this -> bot -> send_tell($name, "You have left the raffle. " . $this -> click_join("join"), 1);
			$this -> output("<font color=#ffff00>" . $name . "</font> has <font color=#ffff00>left</font>" .
			" the raffle :: " . $this -> click_join("join"), 1);
		}
	}



	/*
	Starts the raffle
	*/
	function raffle_start($name, $item)
	{
		if (empty($this -> item))
		{
			$this -> item = $item;
			$this -> item_blank = preg_replace("/<\/a>/U", "", preg_replace("/<a href(.+)>/sU", "", $item));
			$this -> users = "";
			$this -> admin = $name;

			$output = "\n<font color=#ffff00>----------------------------------------------------------</font>\n";
			$output .= "  <font color=#ffff00>" . $name . "</font> has started a raffle for <font color=#ffff00>" .
			$item . "</font> :: " . $this -> click_join("join");
			$output .= "\n<font color=#ffff00>----------------------------------------------------------</font>";

			$this -> output ($output);

			$this -> bot -> send_tell($name, $this -> make_admin($name));
		}
		else
		$this -> bot -> send_tell($name, "A raffle is already running.");
	}



	/*
	Raffle Admin Menu
	*/
	function make_admin($name)
	{
		if (empty($this -> item))
		return "There is no raffle running.";
		else if (!$this -> bot -> core("security") -> check_access($name, "admin") && !($this -> admin == $name))
		return "You did not start the raffle and are not a bot admin.";
		else
		{
			$inside = "<font color=CCInfoHeadline>:::: Raffle Administration ::::</font><font color=CCInfoHeader>\n\n";
			$inside .= "Output channel: \n";
			$inside .= $this -> bot -> core("tools") -> chatcmd(
			"raffle output guild", "Guild")." ";
			$inside .= $this -> bot -> core("tools") -> chatcmd(
			"raffle output group", "Group")." ";
			$inside .= $this -> bot -> core("tools") -> chatcmd(
			"raffle output both", "Both")."\n\n";

			$inside .= "Item: " . $this -> item_blank . "\n\n";

			$inside .= "- ".$this -> bot -> core("tools") -> chatcmd(
			"raffle join", "Join the raffle")."\n";
			$inside .= "- ".$this -> bot -> core("tools") -> chatcmd(
			"raffle leave", "Leave the raffle")."\n\n";

			$inside .= "Cancel raffle: " .
			$this -> bot -> core("tools") -> chatcmd(
			"raffle cancel", "click")."\n\n";

			$inside .= "Announce raffle still open: " .
			$this -> bot -> core("tools") -> chatcmd(
			"raffle reannounce", "click")."\n\n";

			$inside .= "Announce raffle closing soon: " .
			$this -> bot -> core("tools") -> chatcmd(
			"raffle closing", "click")."\n\n";

			$inside .= ":: ".$this -> bot -> core("tools") -> chatcmd(
			"raffle result", "Show winner!")." ::\n";
			return "Raffle <font color=#ffff00>Admin</font> menu: " . $this -> bot -> core("tools") -> make_blob("click to view", $inside);
		}
	}



	/*
	Makes the "click to join" tag...
	*/
	function click_join($val)
	{
		$inside = "<font color=CCInfoHeadline>:::: Join/Leave Raffle ::::</font><font color=CCInfoText>\n\n";
		$inside .= "Raffle for: <font color=CCCCTextColor>" . $this -> item_blank . "</font>\n\n";
		$inside .= "- ".$this -> bot -> core("tools") -> chatcmd(
		"raffle join", "Join the raffle")."\n";
		$inside .= "- ".$this -> bot -> core("tools") -> chatcmd(
		"raffle leave", "Leave the raffle")."\n";
		return $this -> bot -> core("tools") -> make_blob("click to " . $val, $inside);
	}



	/*
	Outputs to the right chan
	*/
	function output($msg, $low=0)
	{
		if (($this -> output == "guild") || ($this -> output == "both"))
		$this -> bot -> send_gc($msg, $low);
		if (($this -> output == "group") || ($this -> output == "both"))
		$this -> bot -> send_pgroup($msg);
	}
}
?>