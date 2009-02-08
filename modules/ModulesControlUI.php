<?php
/*
* GUI to enable and disable modules. Changes only have effect on restarts of the bot though.
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
* File last changed at $LastChangedDate: 2007-07-25 11:54:01 -0700 (Wed, 25 Jul 2007) $
* Revision: $Id: AccessControlGUI.php 817 2007-07-25 18:54:01Z shadowmaster $
*/

$modulecontrol_gui = new ModuleControlGUI($bot);

/*
The Class itself...
*/
class ModuleControlGUI extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> register_command("tell", "modules", "OWNER");

		$this -> help['description'] = "Allows you to enable and disable modules.";
		$this -> help['command']['modules'] = "Shows the GUI to enable and disable modules.";
		$this -> help['notes'] = "Changes to the module loading only take effect after a restart of the bot.";
	}

	/*
	This gets called on a tell with the command
	*/
	function command_handler($name, $msg, $origin)
	{
		if (preg_match("/^modules$/i", $msg))
			return $this -> show_stuff();
		elseif (preg_match("/^modules d ([a-z01-9._]+) ([a-z01-9._]+)$/i", $msg, $info))
			return $this -> disable($name, $info[1], $info[2]);
		elseif (preg_match("/^modules e ([a-z01-9._]+) ([a-z01-9._]+)$/i", $msg, $info))
			return $this -> enable($name, $info[1], $info[2]);
		else
		{
			$this -> bot -> send_help($name);
			return false;
		}
	}

	/*
	Lists all module directories and available modules:
	*/
	function show_stuff()
	{
		$sections = $this -> bot -> core("ini") -> listSections();
		natcasesort($sections);

		foreach ($sections as $section)
		{
			$blob .= "<font color='yellow'>" .$section ."</font>\n";

			$keys = $this -> bot -> core("ini") -> listKeys($section);
			natcasesort($keys);

			foreach ($keys as $key)
			{
				$value = $this -> bot -> core("ini") -> get($key,$section);

				if ($value == "TRUE")
				{
					$blob .= "-<font color='green'>" . $key;
					$blob .= "</font> [";
					$blob .= $this -> bot -> core("tools") -> chatcmd("modules d " . $key . " " . $section, "Disable");
					$blob .= "]";
				}
				else
				{
					$blob .= "-<font color='red'>" . $key;
					$blob .= "</font> [";
					$blob .= $this -> bot -> core("tools") -> chatcmd("modules e " . $key . " " . $section, "Enable");
					$blob .= "]";
				}

				$blob .= "\n";
			}


			$blob .= "\n";
		}


		return $this -> bot -> core("tools") -> make_blob("Module List", $blob);
	}

	/*
	Disables a module on next restart:
	*/
	function disable($name, $key, $section)
	{
		$this -> bot -> core("ini") -> set($key,"FALSE", $section);
		$msg = "Disabled " . $section . "/" . $key . ".  You will need to restart the bot for the changes to take effect.";
		return $msg;
	}

	/*
	Enables a module on next restart:
	*/
	function enable($name, $key, $section)
	{
		$this -> bot -> core("ini") -> set($key,"TRUE", $section);
		$msg = "Enabled " . $section . "/" . $key . ".  You will need to restart the bot for the changes to take effect.";
		return $msg;
	}
}
?>