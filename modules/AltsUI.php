<?php
/*
* Alts.php - Manage alternative characters.
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
* Revision: $Id: AltsUI.php 1833 2008-11-30 22:09:06Z alreadythere $
*/

$alts = new Alts($bot);

/*
The Class itself...
*/
class Alts extends BaseActiveModule
{
	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> register_command("all", "alts", "GUEST", array("confirm" => "ANONYMOUS"));
		$this -> register_command("all", "altadmin", "ADMIN");

		$this -> help['description'] = "Shows information about alternative characters.";
		$this -> help['command']['alts [player]'] = "Shows information about [player]. If no player is given it shows information about yoru alts";
		$this -> help['command']['alts add <player>'] = "Adds <player> as your alt.";
		$this -> help['command']['alts del <player>'] = "Removes <player> from your alt list.";
		$this -> help['command']['altadmin add <main> <alt>'] = "Adds <alt> as alt to <main>.";
		$this -> help['command']['altadmin del <main> <alt>'] = "Removes <alt> as alt from <main>.";

		$this -> bot -> core("settings") -> create("Alts", "Security", TRUE, "Should security restrictions be enabled to prevent users from gaining higher access levels by adding alts with higher access level when Usealts for the security module is enabled?");
	}

	function command_handler($name, $msg, $origin)
	{
		$security = FALSE;

		$vars = explode(' ', strtolower($msg));

		$command = $vars[0];

		if (($this -> bot -> core("settings") -> get("Alts", "Security") == TRUE) && ($this -> bot -> core("settings") -> get("Security", "UseAlts") == TRUE))
		{
				$security = TRUE;
		}

		switch($command)
		{
			case 'alts':
				switch($vars[1])
				{
					case 'add':
						return $this -> add_alt($name, $vars[2]);
					case 'del':
					case 'rem':
						return $this -> del_alt($name, $vars[2]);
					case '':
						return $this -> display_alts($name);
					case 'confirm':
						return $this -> confirm($name, $vars[2]);
					default:
						return $this -> display_alts($vars[1]);
				}
			case 'altadmin':
				switch($vars[1])
				{
					case 'add':
						if ($security)
						{
							if ($this -> bot -> core("security") -> get_access_level($name) < $this -> bot -> core("security") -> get_access_level($vars[2]))
							{
								return "##error##Character ##highlight##$vars[2]##end## has a higher security level then you, so you cannot add ##highlight##$vars[3]##end## to ##highlight##$vars[2]##end##'s alts.##end##";
							}
							elseif ($this -> bot -> core("security") -> get_access_level($name) < $this -> bot -> core("security") -> get_access_level($vars[3]))
							{
								return "##error##Character ##highlight##$vars[3]##end## has a higher security level then you, so you cannot add ##highlight##$vars[3]##end## to ##highlight##$vars[2]##end##'s alts.##end##";
							}
							else
							{
								return $this -> add_alt($vars[2], $vars[3], 1);
							}
						}
						else
						{
							return $this -> add_alt($vars[2], $vars[3], 1);
						}
					case 'rem':
					case 'del':
						return $this -> del_alt($vars[2], $vars[3]);
					default:
						return "Unknown Subcommand: ##highlight##".$vars[1]."##end##";
				}
			default:
				return "Broken plugin, recieved unhandled command: $command";
		}
		return false;
	}


	function display_alts($name)
	{
		if (!$this -> bot -> core("chat") -> get_uid($name))
		{
			return "##error##Character ##highlight##$name##end## does not exist.##end##";
		}

		$whois = $this -> bot -> core("whois") -> lookup($name);
		$alts = $this -> bot -> core("alts") -> show_alt($name);
		if($this -> bot -> game == "aoc")
			$retstr = "{$whois['nickname']} ({$whois['level']} / {$whois['class']}) - ";
		else
			$retstr = "{$whois['firstname']}' ##{$whois['faction']}##{$whois['nickname']}##end##' {$whois['lastname']} ({$whois['level']} / ##lime## {$whois['at_id']}##end## {$whois['profession']}) - ";

		if ($alts['alts'])
		{
			$retstr .= $alts['list'];
		}
		else
		{
			$retstr .= "has no alts defined!";
		}
		return $retstr;
	}

	/*
	Adds an alt to your alt list
	*/
	function add_alt($name, $alt, $admin = 0)
	{
		$security = FALSE;
		$name = ucfirst(strtolower($name));
		$alt = ucfirst(strtolower($alt));
		if (($this -> bot -> core("settings") -> get("Alts", "Security") == TRUE) && ($this -> bot -> core("settings") -> get("Security", "UseAlts") == TRUE) && ($admin == 0))
		{
				$security = TRUE;
		}

		//Check that we're not trying to register ourself as an alt
		if($name == $alt)
		{
			return "##error##You cannot register yourself as your own alt.##end##";
		}

		//Check that $name is a valid character
		if (!$this -> bot -> core("chat") -> get_uid($name))
		{
			return "##error##Character ##highlight##$name##end## does not exist.##end##";
		}

		//Check that the alt is a valid character
		if (!$this -> bot -> core("chat") -> get_uid($alt))
		{
			return "##error##Character ##highlight##$alt##end## does not exist.##end##";
		}

		//Establish the main of the caller
		$main = $this -> bot -> core("alts") -> main($name);

		//Check that the alt is not already registered
		$query = "SELECT main, confirmed FROM #___alts WHERE main='$alt' OR alt='$alt'";
		$result = $this -> bot -> db -> select($query, MYSQL_ASSOC);
		if(!empty($result))
		{
			if($result[0]['confirmed'] == 1)
				return("##highlight##$alt##end## is already registered as an alt of ##highlight##{$result[0]['main']}##end##.");
			else
				return("##highlight##$alt##end## is already an unconfirmed alt of ##highlight##{$result[0]['main']}##end##.");
		}

		if ($security)
		{
			// Check if the Alt being Added has Higher Security
			if ($this -> bot -> core("security") -> get_access_level($name) < $this -> bot -> core("security") -> get_access_level($alt))
			{
				return "##error##Character ##highlight##$alt##end## is Higher User Level and Cannot be Added as your Alt.##end##";
			}
		}

		$alt = ucfirst(strtolower($alt));
		$main = ucfirst(strtolower($main));

		if ($this -> bot -> core("settings") -> get("Alts", "Confirmation") && ($admin == 0))
		{
			$this -> bot -> db -> query("INSERT INTO #___alts (alt, main, confirmed) VALUES ('$alt', '$main', 0)");
			$inside = "##blob_title##  :::  Alt Confirmation Request :::##end##\n\n";
			$inside .= "##blob_text## $main has added you as an Alt\n\n ".$this -> bot -> core("tools") -> chatcmd("alts confirm ".$main, "Click here")." to Confirm.";
			$this -> bot -> send_tell($alt, "Alt Confirmation :: ".$this -> bot -> core("tools") -> make_blob("Click to view", $inside));
			return "##highlight##$alt##end## has been registered but Now requires Confirmation.";
		}
		$this -> bot -> db -> query("INSERT INTO #___alts (alt, main) VALUES ('$alt', '$main')");
		$this -> bot -> core("alts") -> add_alt($main, $alt);
		$this -> bot -> core("points") -> check_alts($main);
		return "##highlight##$alt##end## has been registered as a new alt of ##highlight##$main##end##.";
	}

	/*
	Removes an alt form your alt list
	*/
	function del_alt($name, $alt)
	{
		$name = ucfirst(strtolower($name));
		$alt = ucfirst(strtolower($alt));

		//Establish the main of the caller
		$main = $this -> bot -> core("alts") -> main($name);

		//Check that we're not trying to register ourself as an alt
		if($name == $alt && $name == $main)
		{
			return "##error##You cannot remove yourself as not being your own alt.##end##";
		}

		// Make sure $name and $alt match legal pattern for character names (only letters or numbers)
		if (!preg_match("/^[a-z0-9]+$/i", $name))
		{
			return "##error##Illegal character name ##highlight##$name##end##!##end##";
		}
		if (!preg_match("/^[a-z0-9]+$/i", $name))
		{
			return "##error##Illegal character name ##highlight##$alt##end##!##end##";
		}

		//Chech that alt is indeed an alt of the caller
		$alt = ucfirst(strtolower($alt));
		$main = ucfirst(strtolower($main));
		$result = $this -> bot -> db -> select("SELECT main FROM #___alts WHERE alt = '$alt' AND main = '$main'");
		if (empty($result))
		{
			return "##highlight##$alt##end## is not registered as an alt of ##highlight##$main##end##.";
		}
		else
		{
			$this -> bot -> db -> query("DELETE FROM #___alts WHERE alt = '" . ucfirst(strtolower($alt)) . "'");
			$this -> bot -> core("alts") -> del_alt($main, $alt);
			return "##highlight##$alt##end## has been removed from ##highlight##$main##end##s alt-list.";
		}
	}

	function confirm($alt, $main)
	{
		$result = $this -> bot -> db -> select("SELECT confirmed FROM #___alts WHERE alt = '$alt' AND main = '$main'");
		if(!empty($result))
		{
			if($result[0][0] == 0)
			{
				$this -> bot -> db -> query("UPDATE #___alts SET confirmed = 1 WHERE main = '$main' AND alt = '$alt'");
				$this -> bot -> core("alts") -> add_alt($main, $alt);
				return "##highlight##$alt##end## has been confirmed as a new alt of ##highlight##$main##end##.";
			}
			else
				return("##highlight##$alt##end## is already a confirmed alt of ##highlight##$main##end##.");
		}
		else
			return "##error####highlight##$alt##end## is not registered as an alt of ##highlight##$main##end##.##end##";
	}
}
?>