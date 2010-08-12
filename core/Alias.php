<?php
/*
* Alias.php 
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
$alias = new Alias($bot);
class Alias extends BaseActiveModule
{

	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));

		$this -> register_command('all', 'alias', 'GUEST', array("admin" => "ADMIN"));
		$this -> register_module("alias");
		$this -> register_event("connect");
		$this -> help['description'] = "Add Alias's like what you are commonly called to be used in other modules.";
		$this -> help['command']['alias add <Alias>']="Add Alias.";
		$this -> help['command']['alias del <Alias>']="Delete Alias.";
		$this -> help['command']['alias rem <Alias>']="Delete Alias.";
		$this -> help['command']['alias admin add <nickname> <Alias>']="Add Alias to Nickname.";
		$this -> help['command']['alias admin del <Alias>']="Delete Alias.";
		$this -> help['command']['alias admin rem <Alias>']="Delete Alias.";
		$this -> help['command']['alias <name>']="Show Alias's associated with <name> and Alts.";
		$this -> help['command']['alias']="Show Alias's associated with you and your Alts.";
		$this -> help['command']['alias list']="Show all Alias's.";
		$this -> bot -> db -> query("CREATE TABLE IF NOT EXISTS " . $this -> bot -> db -> define_tablename("alias", "false") . " (
										alias VARCHAR(30) PRIMARY KEY, 
										nickname VARCHAR(30),
										main INT(1) Default '0'
									)");
	}

	function connect()
	{
		$this->create_caches();
	}

	function create_caches()
	{
		$this->alias = array();
		$this->main = array();
		$aliass = $this->bot->db->select("SELECT alias, nickname, main FROM #___alias");
		if (! empty($aliass))
		{
			foreach ($aliass as $alias)
			{
				$this->alias[strtolower($alias[0])] = $alias[1];
				if ($alias[2] == 1)
					$this->main[$this->bot->core("alts")->main($alias[1])] = $alias[0];
			}
		}
	}

	function command_handler($name, $msg, $origin)
	{
		$security = FALSE;
		$vars = explode(' ', $msg);
		$vars[0] = strtolower($vars[0]);
		$vars[1] = strtolower($vars[1]);
		switch ($vars[0])
		{
			case 'alias':
				switch ($vars[1])
				{
					case 'add':
						return $this->add_alias($name, $vars[2]);
					case 'del':
					case 'rem':
						return $this->del_alias($name, $vars[2]);
					case 'main':
						return $this->set_main($name, $vars[2]);
					case '':
						return $this->get_alias($name);
					case 'admin':
						switch (strtolower($vars[2]))
						{
							case 'add':
								$vars[3] = ucfirst(strtolower($vars[3]));
								return $this->add_alias($vars[3], $vars[4]);
							case 'rem':
							case 'del':
								return $this->del_alias_admin($vars[3]);
							default:
								return "Unknown Subcommand of alias admin: " . $vars[1];
						}
					default:
						return $this->get_alias($vars[1]);
				}
				break;
			default:
				return "Broken plugin, received unhandled command: " . $vars[0];
		}
	}

	function add_alias($name, $alias)
	{
		if (! $this->bot->core('player')->id($name))
			return "##error##Character ##highlight##" . $name . "##end## does not exist.##end##";
		if (strlen($alias) < 3)
			return "##error##Alias ##highlight##" . $alias . "##end## is too Short. (min 3)##end##";
		$name = $this->bot->core("alts")->main($name);
		$result = $this->bot->db->select("SELECT nickname FROM #___alias WHERE alias = '$alias'");
		if (empty($result))
		{
			$name = ucfirst(strtolower($name));
			if (! isset($this->main[$name]))
			{
				$mainmsg = " and set as Main Alias";
				$this->main[$name] = $alias;
				$main = 1;
			}
			else
				$main = 0;
			$this->bot->db->query("INSERT INTO #___alias (alias, nickname, main) VALUES ('" . $alias . "', '" . $name . "', " . $main . ")");
			$this->alias[strtolower($alias)] = $name;
			return "##highlight##" . $alias . "##end## Added as Alias of ##highlight##" . $name . "##end##" . $mainmsg;
		}
		else
		{
			return "##highlight##" . $alias . "##end## is Already an Alias off ##highlight##" . $result[0][0] . "##end##.";
		}
	}

	function del_alias($name, $alias)
	{
		$name = $this->bot->core("alts")->main($name);
		$result = $this->bot->db->select("SELECT nickname, main FROM #___alias WHERE alias = '$alias'");
		if (! empty($result))
		{
			if ($result[0][0] == $name)
			{
				$this->bot->db->query("DELETE FROM #___alias WHERE alias = '$alias'");
				unset($this->alias[$alias]);
				if ($result[0][1] == 1)
					unset($this->main[$name]);
				return "Alias ##highlight##" . $alias . "##end## Deleted.";
			}
			else
			{
				return "Alias ##highlight##" . $alias . "##end## Belongs to ##highlight##" . $result[0][0] . "##end## and can not be Deleted by you.";
			}
		}
		else
			return "Alias ##highlight##" . $alias . "##end## Not found.";
	}

	function del_alias_admin($alias)
	{
		$result = $this->bot->db->select("SELECT nickname, main FROM #___alias WHERE alias = '$alias'");
		if (! empty($result))
		{
			$this->bot->db->query("DELETE FROM #___alias WHERE alias = '$alias'");
			unset($this->alias[$alias]);
			if ($result[0][1] == 1)
				unset($this->main[$result[0][0]]);
			return "Alias ##highlight##" . $alias . "##end## Deleted.";
		}
		else
			return "Alias ##highlight##" . $alias . "##end## Not found.";
	}

	function get_alias($name)
	{
		$name = ucfirst(strtolower($name));
		// Create Header
		$inside = "<center>##ao_ccheader##:::: Alias List ::::##end##</center>\n";
		$aliases = $this->alias;
		if (! empty($aliases))
		{
			if ($name == "List")
			{
				foreach ($aliases as $alias => $nickname)
				{
					$inside .= "\n##lightyellow##" . $alias;
					$inside .= "   " . $this->bot->core("tools")->chatcmd("whois " . $nickname, $nickname);
				}
			}
			else
			{
				$main = $this->bot->core("alts")->main($name);
				foreach ($aliases as $alias => $nickname)
				{
					if ($main == $nickname)
					{
						$inside .= "\n##lightyellow##" . $alias;
						$inside .= "   " . $this->bot->core("tools")->chatcmd("whois " . $nickname, $nickname);
					}
				}
			}
		}
		else
			$inside = "<center>##ao_ccheader##:::: No Alias's Found ::::##end##</center>\n";
		if ($name == "List")
			$forwho = "Alias List :: ";
		else
			$forwho = "Alias List :: " . $name . " and Alts :: ";
		return $forwho . $this->bot->core("tools")->make_blob("click to view", $inside);
	}

	function set_main($name, $alias)
	{
		$alias = strtolower($alias);
		$main = $this->bot->core("alts")->main($name);
		if (isset($this->main[$main]))
		{
			if (strtolower($this->main[$main]) == $alias)
				return "##highlight##" . $alias . "##end## is Already you main alias";
			$amain = $this->bot->core("alts")->main($this->alias[$alias]);
			if ($main != $amain)
				return "##highlight##" . $alias . "##end## is not your Alias so cannot be set as main";
				//set all alias's for $name and alts to not main
			$this->bot->db->query("UPDATE #___alias SET main = 0 WHERE nickname = '" . $main . "'");
			$this->bot->db->query("UPDATE #___alias SET main = 1 WHERE alias = '" . $alias . "'");
			$this->create_caches();
			return "Alias Main set";
		}
		elseif (isset($this->alias[$alias]))
		{
			$amain = $this->bot->core("alts")->main($this->alias[$alias]);
			if ($main != $amain)
				return "##highlight##" . $alias . "##end## is not your Alias so cannot be set as main";
				//set all alias's for $name and alts to not main
			$sql .= "UPDATE #___alias SET main = 0 WHERE nickname = '" . $main . "'";
			$alts = $this->bot->core("alts")->get_alts($main);
			if (! empty($alts))
			{
				foreach ($alts as $alt)
				{
					$sql .= " OR nickname = '" . $alt . "'";
				}
			}
			$this->bot->db->query($sql);
			$this->bot->db->query("UPDATE #___alias SET main = 1 WHERE alias = '" . $alias . "'");
			$this->create_caches();
			return "Alias Main set";
		}
		else
			return "Alias not Found";
	}

	function get_main($name)
	{
		$main = $this->bot->core("alts")->main($name);
		if (isset($this->main[$main]))
			return $this->main[$main];
		else
			return FALSE;
	}
}
?>