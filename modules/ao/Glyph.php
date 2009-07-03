<?php
/*
* Glyph.php - Raffles inferno glyphs.
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
* File last changed at $LastChangedDate: 2008-11-30 23:09:06 +0100 (sÃ¸, 30 nov 2008) $
* Revision: $Id: Glyph.php 1833 2008-11-30 22:09:06Z alreadythere $
*/


$glyph = new Glyph($bot);



/*
The Class itself...
*/
class Glyph extends BaseActiveModule
{
	var $raffle;
	var $aban;
	var $enel;
	var $ocra;
	var $users;



	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> raffle = false;
		$this -> register_command("tell", "glyph", "GUEST");
	}



	/*
	This gets called on a tell with the command
	*/
	function command_handler($name, $msg, $origin)
	{
		if (preg_match("/^glyph start a ([0-9]+) e ([0-9]+) o ([0-9]+)$/i", $msg, $info))
		$this -> start_glyph($name, $info[1], $info[2], $info[3]);
		else if (preg_match("/^glyph start/i", $msg, $info))
		$this -> bot -> send_tell($name, "Usage: glyph start a <aban number> e <enel number> o <ocra number>\n" .
		"Example: glyph start a 3 e 2 o 5");
		else if (preg_match("/^glyph cancel/i", $msg, $info))
		$this -> cancel_glyph($name);
		else if (preg_match("/^glyph (result|end)/i", $msg, $info))
		$this -> result_glyph($name);
		else if (preg_match("/^glyph join$/i", $msg))
		$this -> join_glyph($name);
		else if (preg_match("/^glyph leave$/i", $msg))
		$this -> leave_glyph($name);
		else
		$this -> bot -> send_help($name);
	}



	/*
	Starts glyph raffle
	*/
	function start_glyph($name, $a, $e, $o)
	{
		if ($this -> bot -> core("security") -> check_access($name, "leader"))
		{
			if (!$this -> raffle)
			{
				$this -> raffle = true;
				$this -> aban = $a;
				$this -> enel = $e;
				$this -> ocra = $o;
				$this -> users["aban"] = array();
				$this -> users["enel"] = array();
				$this -> users["ocra"] = array();

				$this -> bot -> send_pgroup("##highlight##$name##end## has ##highlight##started##end## " .
				"a raffle for ##highlight##$a##end## Aban glyphs, " .
				"##highlight##$e##end## Enel glyphs, " .
				"##highlight##$o##end## Ocra glyphs. :: "  . $this -> click_join() .
				"\n##highlight##" .
				" - Glyphs are for your own use only. You may NOT sell them!\n" .
				" - A won glyph will cost you 2 raidpoints.##end##");
			}
			else
			$this -> bot -> send_tell($name, "A glyph raffle has already been started. \"!glyph cancel\" to cancel it.");
		}
		else
		$this -> bot -> send_tell($name, "You must be a raidleader to do this");
	}



	/*
	Cancels a raffle

	*/
	function cancel_glyph($name)
	{
		if ($this -> bot -> core("security") -> check_access($name, "leader"))
		{
			if ($this -> raffle)
			{
				$this -> raffle = false;
				$this -> bot -> send_pgroup("##highlight##$name##end## has ##highlight##canceled##end## the glyph raffle.");
			}
			else
			$this -> bot -> send_tell($name, "No glyph raffle running.");
		}
		else
		$this -> bot -> send_tell($name, "You must be a raidleader to do this");
	}



	/*
	Produces results of raffle

	*/
	function result_glyph($name)
	{
		if ($this -> bot -> core("security") -> check_access($name, "leader"))
		{
			if ($this -> raffle)
			{
				$this -> raffle = false;

				$aban = "Winners for the ##highlight##aban##end## glyph: ##highlight## ";
				$enel = "Winners for the ##highlight##enel##end## glyph: ##highlight## ";
				$ocra = "Winners for the ##highlight##ocra##end## glyph: ##highlight## ";

				// ABAN
				for ($i = 0; $i < $this -> aban; $i++)
				{
					if (!empty($this -> users["aban"]))
					{
						$users = array_keys($this -> users["aban"]);
						$num = ((int)mt_rand(0, 10000000)) % count($users);
						$aban .= $users[$num] . " ";
						unset($this -> users["aban"][$users[$num]]);
						$this -> bot -> db -> query("UPDATE raid_points SET points = points - 20 WHERE id = " .
						$this -> bot -> core("chat") -> get_uid($users[$num]));
					}
				}

				// ENEL
				for ($i = 0; $i < $this -> enel; $i++)
				{
					if (!empty($this -> users["enel"]))
					{
						$users = array_keys($this -> users["enel"]);
						$num = ((int)mt_rand(0, 10000000)) % count($users);
						$enel .= $users[$num] . " ";
						unset($this -> users["enel"][$users[$num]]);
						$this -> bot -> db -> query("UPDATE raid_points SET points = points - 20 WHERE id = " .
						$this -> bot -> core("chat") -> get_uid($users[$num]));
					}
				}

				// OCRA
				for ($i = 0; $i < $this -> ocra; $i++)
				{
					if (!empty($this -> users["ocra"]))
					{
						$users = array_keys($this -> users["ocra"]);
						$num = ((int)mt_rand(0, 10000000)) % count($users);
						$ocra .= $users[$num] . " ";
						unset($this -> users["ocra"][$users[$num]]);
						$this -> bot -> db -> query("UPDATE raid_points SET points = points - 20 WHERE id = " .
						$this -> bot -> core("chat") -> get_uid($users[$num]));
					}
				}
				$aban .= "##end##\n";
				$enel .= "##end##\n";
				$ocra .= "##end##\n";

				if ($this -> aban == 0)
				$aban = "No ##highlight##aban##end## glyphs where up for raffle.\n";
				if ($this -> enel == 0)
				$enel = "No ##highlight##enel##end## glyphs where up for raffle.\n";
				if ($this -> ocra == 0)
				$ocra = "No ##highlight##ocra##end## glyphs where up for raffle.\n";

				$this -> bot -> send_pgroup("The glyph winners are:##highlight##\n" . $aban . $enel . $ocra .
				"##end##\n##highlight##2##end## raidpoints where deduced from their accounts.");
			}
			else
			$this -> bot -> send_tell($name, "No glyph raffle running.");
		}
		else
		$this -> bot -> send_tell($name, "You must be a raidleader to do this");
	}



	/*
	Join Glyph raffle
	*/
	function join_glyph($name)
	{
		if ($this -> raffle)
		{
			$result = $this -> bot -> db -> select("SELECT points FROM raid_points WHERE id = " .
			$this -> bot -> core("chat") -> get_uid($name));
			if ($result[0][0] < 2)
			$this -> bot -> send_tell("You must have at least 2 raidpoints to join");
			else
			{
				$type = $this -> get_type($name);
				if (!isset($this -> users[$type][$name]))
				{
					$this -> users[$type][$name] = true;
					$this -> bot -> send_pgroup("##highlight##$name##end## has ##highlight##entered##end## the raffle for an ##highlight##$type##end## glyph.");
					$this -> bot -> send_tell($name, "You have joined the raffle for an ##highlight##$type##end## glyph.", 1);
				}
				else
				$this -> bot -> send_tell($name, "You are already in the raffle.");
			}
		}
		else
		$this -> bot -> send_tell($name, "No glyph raffle running");
	}



	/*
	Leave Glyph raffle
	*/
	function leave_glyph($name)
	{
		if ($this -> raffle)
		{
			$type = $this -> get_type($name);
			if (isset($this -> users[$type][$name]))
			{
				unset($this -> users[$type][$name]);
				$this -> bot -> send_pgroup("##highlight##$name##end## has ##highlight##left##end## the gylph raffle.");
				$this -> bot -> send_tell($name, "You have left the raffle.");
			}
			else
			$this -> bot -> send_tell($name, "You are not in the raffle.");
		}
		else
		$this -> bot -> send_tell($name, "No glyph raffle running");
	}



	/*
	Gets user's type
	*/
	function get_type($name)
	{
		$result = $this -> bot -> db -> select("SELECT profession FROM users WHERE id = " .
		$this -> bot -> core("chat") -> get_uid($name));
		$result = $result[0][0];

		if (($result == "Enforcer") || ($result == "Engineer") || ($result == "Keeper") ||
		($result == "Martial Artist") || ($result == "Shade"))
		return "aban";

		else if (($result == "Agent") || ($result == "Bureaucrat") || ($result == "Nano-Technician") ||
		($result == "Soldier"))
		return "enel";

		else
		return "ocra";
	}



	/*
	Makes the "click to join" tag...
	*/
	function click_join()
	{
		$inside = "##blob_title##:::: Join/Leave Glyph Raffle ::::##end##\n\n";
		$inside .= " - ".$this -> bot -> core("tools") -> chatcmd("glyph join", "Join the glyph raffel")."\n";
		$inside .= " - ".$this -> bot -> core("tools") -> chatcmd("glyph leave", "Leave the glyph raffel")."\n";

		return $this -> bot -> core("tools") -> make_blob("click to join", $inside);
	}
}
?>
