<?php
/*
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
///////////////////////////////////
// craftclasses.php 1.2 for BeBot
///////////////////////////////////
// (c) Copyright 2008 by Allan Noer
// All Rights Reserved
// Licensed for distribution under the GPL (Gnu General Public License) version 2.0 or later
///////////////////////////////////
// Updated to 1.1 by Buffarse - Added additional parsing and user feedback to !setclass
// Updated to 1.2 by Getrix - Added "None" profession as many have Alts that they dont want professions on.
//
$craftclasses = new craftclasses($bot);
//////////////////////////////////////////////////////////////////////
// The Class itself...
class craftclasses extends BaseActiveModule
{
	var $bot;
	var $help;
	var $last_log;
	var $start;

	// Constructor
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));
		$this->bot->db->query("CREATE TABLE IF NOT EXISTS " . $this->bot->db->define_tablename("craftingclass", "false") . " (
			id int(11) NOT NULL auto_increment,
			name varchar(32) NOT NULL,
			class1 enum('Alchemist','Architect','Armorsmith','Gemcutter','Weaponsmith','None') NOT NULL,
			class2 enum('Alchemist','Architect','Armorsmith','Gemcutter','Weaponsmith','None') NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY name (name)
			);
		");
		$this->last_log = array();
		$this->output = "group";
		$this->result = "";
		$this->register_event("logon_notify");
		$this->register_command("all", "setcraft", "MEMBER");
		$this->register_command("all", "craft", "MEMBER");
		$this->help['description'] = 'Used to set the crafting classes on a user.';
		$this->help['command']['setcraft [class1] [class2]'] = "Sets the two crafting classes for you. Classes can be Alchemist, Architect, Armorsmith, Gemcutter, Weaponsmith and None";
		$this->help['command']['craft'] = "Shows the classes you currently have assigned to you.";
		$this->bot->core("settings")->create("Craftclasses", "Remind", TRUE, "Should users level 40+ be reminded to set their craft classes?");
		$this->update_table();
	}

	function update_table()
	{
		switch ($this->bot->db->get_version("craftingclass"))
		{
			case 1:
				$this->bot->db->update_table("craftingclass", "class1", "modify", "ALTER IGNORE TABLE #___craftingclass modify `class1` enum('Alchemist','Architect','Armorsmith','Gemcutter','Weaponsmith','None') NOT NULL");
				$this->bot->db->update_table("craftingclass", "class2", "modify", "ALTER IGNORE TABLE #___craftingclass modify `class2` enum('Alchemist','Architect','Armorsmith','Gemcutter','Weaponsmith','None') NOT NULL");
			default:
		}
		$this->bot->db->set_version("craftingclass", 2);
	}

	function command_handler($name, $msg, $origin)
	{
		$output = "";
		if (preg_match("/^setcraft (.+)$/i", $msg, $info))
		{
			$options_str = $info[1];
			$options = array();
			$options = split(" ", $options_str);
			$craftclass = array("Alchemist" , "Architect" , "Armorsmith" , "Gemcutter" , "Weaponsmith" , "None");
			$options[0] = ucwords(strtolower($options[0]));
			$options[1] = ucwords(strtolower($options[1]));
			if (empty($options[0]) || empty($options[1]))
			{
				$output = "You MUST set both craft classes at the same time.";
			}
			elseif ((array_search($options[0], $craftclass) !== false) && (array_search($options[1], $craftclass) !== false))
			{
				$this->bot->db->query('INSERT INTO #___craftingclass (name,class1,class2) VALUES("' . $name . '","' . $options[0] . '","' . $options[1] . '") ON DUPLICATE KEY UPDATE class1=values(class1), class2=values(class2)');
				$this->bot->db->query("UPDATE #___whois set craft1 = '" . $options[0] . "', craft2 = '" . $options[1] . "' WHERE nickname = '" . $name . "'");
				$output = "Thank you for updating your crafting information.";
			}
			else
			{
				$output = "Classes can ONLY be Alchemist, Architect, Armorsmith, Gemcutter, Weaponsmith and None. You MUST set both at the same time.";
			}
		}
		elseif (preg_match("/^craft$/i", $msg, $info))
		{
			$lookup = $this->bot->db->select("SELECT * FROM #___craftingclass WHERE name = '" . $name . "'", MYSQL_ASSOC);
			if (! empty($lookup))
			{
				$output = "Your crafting classes are: " . $lookup[0]['class1'] . " and " . $lookup[0]['class2'];
			}
			else
			{
				$output = "You have no crafting information set. Please use '/tell <botname> <pre>setcraft [class1] [class2]'. Classes can be Alchemist, Architect, Armorsmith, Gemcutter, Weaponsmith and None.";
			}
		}
		return $output;
	}

	function notify($name, $startup = false)
	{
		if ($this->bot->core("settings")->get("Craftclasses", "Remind") && ! $startup)
		{
			$id = $this->bot->core("chat")->get_uid($name);
			$result = $this->bot->core("whois")->lookup($name);
			if (! ($result instanceof BotError))
			{
				if (empty($result["craft1"]) & $result["level"] > 40)
				{
					$msg = "You have no crafting information set and you are above level 40. Please use '/tell <botname> <pre>setcraft [class1] [class2]'. Classes can be Alchemist, Architect, Armorsmith, Gemcutter, Weaponsmith and None. If you havn't picked crafting classes yet this may be the time to do it.";
					$this->bot->send_tell($name, $msg);
				}
			}
		}
	}
}
?>