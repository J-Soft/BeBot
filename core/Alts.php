<?php
/*
* Alts.php - Alternative character management
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
* Revision: $Id: Alts.php 1833 2008-11-30 22:09:06Z alreadythere $
*/

/*
Prepare MySQL database
*/

$alts_core = new Alts_Core($bot);

/*
The Class itself...
*/
class Alts_Core extends BasePassiveModule
{
	private $mains;
	private $alts;

	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS " . $this -> bot -> db -> define_tablename("alts", "false") . " (alt VARCHAR(255) NOT NULL PRIMARY KEY, main VARCHAR(255), confirmed INT DEFAULT '1')");

		$this -> register_module("alts");
		$this -> register_event("cron", "1hour");

		$this -> update_table();
		$this -> create_caches();

		//Create settings for this module
		$this -> bot -> core("settings") -> create ('Alts', "Output", "Fancy", "How would you like your alts list", "Fancy;Old");
		$this -> bot -> core("settings") -> create ('Alts', "Detail", True, "Show level and profession in the alts list");
		$this -> bot -> core("settings") -> create ('Alts', "LastSeen", True, "Show the time we last saw an alt if they are offline");
		$this -> bot -> core("settings") -> create ('Alts', "Confirmation", FALSE, "Does the Alt have to Confirm him Self as an Alt after being Added?");
		$this -> bot -> core("settings") -> create ('Alts', "incAll", FALSE, "Should the Alt that was used to call the info also be listed inside the blob?");
	}

	function update_table()
	{
		switch ($this -> bot -> db -> get_version("alts"))
		{
			case 1:
				$this -> bot -> db -> update_table("alts", "confirmed", "add",
					"ALTER TABLE #___alts ADD `confirmed` INT DEFAULT '1'");
			case 2:
			default:
		}
		$this -> bot -> db -> set_version("alts", 2);
	}


	/*
	Create the caches to look up main and alts
	*/
	function create_caches()
	{
		$this -> mains = array();
		$this -> alts = array();

		$altlist = $this -> bot -> db -> select("SELECT main, alt FROM #___alts WHERE confirmed = 1 ORDER BY main ASC, alt ASC");
		if(empty($altlist))
		{
			return;
		}

		$curmain = "";
		foreach ($altlist as $curalt)
		{
			// Check if new main, if yes create entry for alts cache:
			if ($curmain != ucfirst(strtolower($curalt[0])))
			{
				$curmain = ucfirst(strtolower($curalt[0]));
				$this -> alts[$curmain] = array();
			}

			// Now add current alt vs main relation to the caches:
			$altname = ucfirst(strtolower($curalt[1]));
			$this -> mains[$altname] = $curmain;
			$this -> alts[$curmain][$altname] = $altname;
		}

		$this -> bot -> core("security") -> cache_mgr("del", "maincache", "");
	}

	function cron()
	{
		$this -> create_caches();
	}

	// Adds new alt to cache
	function add_alt($main, $alt)
	{
		$main = ucfirst(strtolower($main));
		$alt = ucfirst(strtolower($alt));

		$this -> bot -> core("security") -> cache_mgr("add", "main", $main);
		$this -> bot -> core("security") -> cache_mgr("add", "main", $alt);
		if (!isset($this -> alts[$main]))
		{
			$this -> alts[$main] = array();
		}
		$this -> alts[$main][$alt] = $alt;
		asort($this -> alts[$main]);
		$this -> mains[$alt] = $main;
	}

	// Removes alt from cache
	function del_alt($main, $alt)
	{
		$this -> bot -> core("security") -> cache_mgr("del", "main", ucfirst(strtolower($main)));
		unset($this -> mains[ucfirst(strtolower($alt))]);
		unset($this -> alts[ucfirst(strtolower($main))][ucfirst(strtolower($alt))]);
	}

	/*
	Return main char
	*/
	function main($char)
	{
		$char = ucfirst(strtolower($char));
// 		$char = ucfirst(strtolower($this -> bot -> core("chat") -> get_uname($char)));
		if (isset($this -> mains[$char]))
		{
			return $this -> mains[$char];
		}
		else
		{
			return $char;
		}
	}

	/*
	Return array of alts
	*/
	function get_alts($char)
	{
		if(is_numeric($char))
		{
			$char = $this -> bot -> core("player") -> name($char);
		}

		$ret = array();
		if (isset($this -> alts[ucfirst(strtolower($char))]))
		{
			foreach($this -> alts[ucfirst(strtolower($char))] as $curalt)
			{
				$ret[] = $curalt;
			}
		}
		return $ret;
	}


	function old_output($who, $returntype=0)
	{
		$main = $this -> main($who);
		$alts = $this -> get_alts($main);

		if (empty($alts))
		{
			$ret['alts'] = false;
			$ret['list'] = "";
		}
		else
		{
			$ret['alts'] = true;
			$ret['list'] = $this -> make_alt_blob($main, ucfirst(strtolower($who)), $alts, $returntype);
		}

		return $ret;
	}

	/*
	Return main char
	*/
	function make_alt_blob($main, $who, $alts, $returntype)
	{
		$result = "##highlight##::: " . $main . "'s Alts :::##end##\n\n";
		foreach ($alts as $alt)
		{
			$result .= $this -> bot -> core("tools") -> chatcmd("whois ".$alt, $alt)."\n";
		}

		if ($main == $who)
		{
			$title = "Alts";
		}
		else
		{
			$title = $main . "´s alts";
		}

		if ($returntype == 1)
		{
			return $result;
		}
		else
		{
			return $this -> bot -> core("tools") -> make_blob($title, $result);
		}
	}

	/*
	Show fancy alts list
	*/
	function fancy_output($name, $returntype)
	{
		if($this -> bot -> core("player") -> id($name))
		{
			$name = ucfirst(strtolower($name));
			$whois = $this -> bot -> core("whois") -> lookup($name);
			if ($whois instanceof BotError)
				$whois = array('nickname' => $name);
			$main = $this -> main($name);
			$alts = $this -> get_alts($main);

			//If this is not the main set the main as the first alt listed
			if($name != $main || ($alts && $this -> bot -> core("settings") -> get('Alts', "incAll")))
			{
				array_unshift($alts, $main);
			}

			if (empty($alts))
			{
				$ret['alts'] = false;
			}
			else
			{
				$ret['alts'] = true;
			}

			$ret['list'] =  $this -> make_info_blob($whois, $main, $alts, $returntype);
			return $ret;
		}
		else
		{
			return("##highlight##$name##end## does not exist.");
		}
	}

	/*
	Make a big blob
	*/
	function make_info_blob($whois, $main, $alts='', $returntype)
	{
		if (!empty($alts))
		{
			$window = "##normal##:::  $main's alts  :::##end##\n";
			foreach ($alts as $alt)
			{
				if($alt != $whois['nickname'] || $this -> bot -> core("settings") -> get('Alts', "incAll"))
				{
					$window .= $this -> bot -> core("tools") -> chatcmd("whois ".$alt, $alt)."</a>";

					$online = $this -> bot -> core("online") -> get_online_state($alt);
					if ($online['status'] != -1)
						$window .= " " . $online;

					if ($this -> bot -> core("settings") -> get('Alts', 'Detail'))
					{
						$whoisalt = $this -> bot -> core("whois") -> lookup($alt);
						if ($whoisalt instanceof BotError)
							$whoisalt = array('nickname' => $alt);
						if (!empty($whoisalt['level']))
						{
							$window .= "\n##normal## - (##highlight##" . $whoisalt['level'] . "##end##";
							if($this -> bot -> game == "ao")
								$window .= "/##lime##" . $whoisalt['at_id'] . "##end##";
							$window .= " " . $whoisalt['profession'] . ")##end##";
						}
						unset($whoisalt);
					}

					if ($online['status'] <= 0)
					{
						if ($this -> bot -> core("settings") -> get('Alts', 'LastSeen'))
						{
							if ($this -> bot -> core("online") -> get_last_seen($alt))
							{
								$time = gmdate($this -> bot -> core("settings") -> get("Time", "FormatString"), $this -> bot -> core("online") -> get_last_seen($alt));
								$window .= "\n##normal## - Last seen at:##highlight## $time##end####end##";
							}
						}
					}

					$window .="\n";
				}
			}
		}

		if (strtolower($whois['nickname']) == strtolower($main))
		{
			$title = "Alts";
		}
		else
		{
			$title = $main . "´s alts";
		}

		if ($returntype == 1)
		{
			return $window;
		}
		else
		{
			return ($this -> bot -> core("tools") -> make_blob($title, $window));
		}
	}

	/*
	Show mains/alts
	You should use this function when calling your alts list
	This way you ensure that the formatting is the same across all modules
	*/
	function show_alt($who, $returntype=0)
	{
		switch($this -> bot -> core("settings") -> get('Alts', 'Output'))
		{
			case 'Old':
				return($this-> old_output(ucfirst(strtolower($who)), $returntype));
				break;
			case 'Fancy':
				return($this-> fancy_output(ucfirst(strtolower($who)), $returntype));
				break;
			default:
				return 'Settings module required for this module to work properly!';
				break;
		}
	}

}
?>