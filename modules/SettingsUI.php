<?php
/*
* SettingsInterface.php - Settings Managment Interface.
* Version: 0.0
* Created by Andrew Zbikowski <andyzib@gmail.com> (AKA Glarawyn, RK1)
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
* Revision: $Id: SettingsUI.php 1833 2008-11-30 22:09:06Z alreadythere $
*/

$setconf = new SetConf($bot);

/*
The Class itself...
*/
class SetConf extends BaseActiveModule
{ // Start Class
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));
	
		$this -> help['description'] = "Setting management interface.";
		$this -> help['command']['settings'] = "Shows the settings interface";
		$this -> help['command']['set <module> <setting> <value>'] = "Sets the setting <setting> for module <module> to <value>.";

		$this -> register_command("all", "settings", "OWNER");
		$this -> register_alias("settings", "set");
	}

	/*
	This function handles all the inputs and returns FALSE if the
	handler should not send output, otherwise returns a string
	sutible for output via send_tell, send_pmodule, and send_gc.
	*/
	function command_handler($name, $msg, $source)
	{ // Start function process_command()
		if (preg_match("/^settings (.+?) (.+?) (.+)$/i", $msg, $info))
		{
			return $this -> change_setting($name, $info[1], $info[2], $info[3]);
		}
		elseif (preg_match("/^settings (.+?) (.+?)$/i", $msg, $info))
		{
			return $this -> change_setting($info[1], $info[2], "");
		}
		elseif (preg_match("/^settings (.+)$/i", $msg, $info))
		{
			return $this -> show_module($info[1]);
		}
		else
		{
			return $this -> show_all_modules();
		}
	} // End function process_command()

	/*
	Retruns a click window with the setting modules.
	*/
	function show_all_modules()
	{ // Start function show_all_modules()
		$sql = "SELECT DISTINCT module FROM #___settings WHERE hidden = FALSE ORDER BY module";
		$result = $this -> bot -> db -> select($sql);
		if (empty($result))
		{
			return "No settings defined.";
		}

		$output = "##ao_infoheader##Setting groups for <botname>:##end##\n\n";
		foreach ($result as $module)
		{
			if ($module[0] <> "")
			{
				$output .= $this -> bot -> core("tools") -> chatcmd("settings ".$module[0], $module[0])."\n";
			}
		}
		return $this -> bot -> core("tools") -> make_blob("Settings groups for <botname>", $output);
	} // End function show_all_modules()

	/*
	Returns a click window with settings for a specific module.
	*/
	function show_module($module)
	{ // Start function show_module()
		$module = str_replace (" ", "_", $module); // FIXME: Do regexp right and shouldn't need this?
		$sql = "SELECT setting, value, datatype, longdesc, defaultoptions FROM #___settings ";
		$sql .= "WHERE module = '".$module."' AND hidden = FALSE ";
		$sql .= "ORDER BY disporder, setting";
		$result = $this -> bot -> db -> select($sql);
		if (empty($result))
		{
			return "No settings for module ".$module;
		}
		$inside = "##ao_infoheader##Settings for ".$module."##end##\n\n";
		foreach ($result as $setting)
		{ // 0 = setting, 1 = value, 2 = datatype, 3 = longdesc, 4 = defaultoptions
			// Provide generic description if none exists.
			if (empty($setting[3]))
			{
				if ($setting[2] == "int" || $setting[2] == "float")
				{
					$longdesc = "Numeric";
				}
				elseif ($setting[2] == "bool")
				{
					$longdesc = "On/Off";
				}
				elseif ($setting[2] == "string")
				{
					$longdesc = "Text String";
				}
				else
				{
					$longdesc = "Not configured.";
				}
			}
			else
			{
				$longdesc = stripslashes($setting[3]);
			}

			// Make configuration links if options are provided.
			$options = explode(";", $setting[4]);

			$optionlinks = "  ##ao_infotextbold##Change to: [";
			foreach ($options as $option)
			{
				$optionlinks .= " ".$this -> bot -> core("tools") -> chatcmd("set ".$module." ".$setting[0]." ".$option, $option)." |";
			}
			$optionlinks = rtrim($optionlinks, "|");
			$optionlinks .= "]##end##";

			// Setting: Value
			// Description: longdesc
			// Change To: optionlinks
			// Make inside data...
			if (strtoupper($setting[1]) == "TRUE")
			{
				$setting[1] = "On";
			}
			elseif (strtoupper($setting[1]) == "FALSE")
			{
				$setting[1] = "Off";
			}

			if (strtolower($setting[0]) == "password") // Mask passwords
			{
				$inside .= "##ao_infoheadline##".$setting[0].":##end##  ##ao_infotextbold##************##end##\n";
			}
			elseif (preg_match("/^#[0-9a-f]{6}$/i", $setting[1])) // Show HTML Color Codes in Color
			{
				$inside .= "##ao_infoheadline##".$setting[0].":##end##  <font color=".$setting[1].">".$setting[1]."</font>\n";
			}
			else // Normal Setting Display.
			{
				$inside .= "##ao_infoheadline##".$setting[0].":##end##  ##ao_infotextbold##".$setting[1]."##end##\n";
			}

			$inside .= "  ##ao_infotextbold##Description:##end## ##ao_infotext##".$longdesc."##end##\n";
			if (count($options) > 1)
			{
				$inside .= $optionlinks."\n\n";
			}
			else
			{
				$inside .= "/tell <botname> <pre>set ".$module." ".$setting[0]." &lt;new value&gt;\n\n";
			}
		}
		return $this -> bot -> core("tools") -> make_blob("Settings for ".$module, $inside);
	} // End fucnction show_module()

	function change_setting($user, $module, $setting, $value)
	{ // Start function change_setting()
		$module = $this -> bot -> core("settings") -> remove_space($module);
		$setting = $this -> bot -> core("settings") -> remove_space($setting);
		if (!($this -> bot -> core("settings") -> exists($module, $setting)))
		{
			return "Setting ".$setting." for module ".$module." does not exist.";
		}

		$datatype = $this -> bot -> core("settings") -> get_data_type($this -> bot -> core("settings") -> get($module, $setting));
		// Deal with possibly bad input from the user before continuing.
		switch ($datatype)
		{
			case "bool": // A bool value comes in as on or off.
				$value = strtolower($value);
				if ($value == "on")
				{
					$value = TRUE;
				}
				elseif ($value == "off")
				{
					$value = FALSE;
				}
				else
				{
					return "Unrecgonized value for setting ".$setting." for module ".$module.". No change made.";
				}
			break;
			case "null":
				// If the string is null, change it to a null value.
				// Otherwise, $value will be saved as a string. (No modification needed)
				if (strtolower($value) == "null")
				{
					$value = NULL;
				}
				break;
			case "array": // Changing arrays are not supported! :D
				return "Modifying array values is not supported in this interface. See the help for ".$module;
				break;
			default:
				$value = $this -> bot -> core("settings") -> set_data_type($value, $datatype);
				break;
		}
		$this -> bot -> core("settings") -> set_change_user($user);
		$result = $this -> bot -> core("settings") -> save($module, $setting, $value);
		$this -> bot -> core("settings") -> set_change_user("");
		if ($result['error'])
		{
			return $result['errordesc'];
		}
		else
		{
			if ($datatype == "bool")
			{
				if ($value) {$value = "On";}
				else {$value = "Off";}
			}
			return "Changed setting ".$setting." for module ".$module." to ".strval($value)." [".$this -> show_module($module)."]";
		}

	} // End function change_setting()

} // End of Class
?>