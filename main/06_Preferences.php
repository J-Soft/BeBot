<?php
/*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2007 Thomas Juberg Stens?s, ShadowRealm Creations and the BeBot development team.
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
* File last changed at $LastChangedDate: 2008-11-30 18:55:36 +0000 (Sun, 30 Nov 2008) $
* Revision: $Id: 06_Preferences.php 35 2008-11-30 18:55:36Z temar $
*/

$preferences_core = new Preferences_core(&$bot);

class Preferences_core extends BasePassiveModule
{
	private $cache;

	/*
	Constructor. Pass over a reference to bot and create preferences/settings
	*/
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$query = 'CREATE TABLE IF NOT EXISTS ' . $this -> bot -> db -> define_tablename('preferences_def', 'true');
		$query .= '(ID INT NOT NULL AUTO_INCREMENT PRIMARY KEY, ';
		$query .= 'module VARCHAR(30), ';
		$query .= 'name VARCHAR(30), ';
		$query .= 'description VARCHAR(255), ';
		$query .= 'default_value VARCHAR(25), ';
		$query .= 'possible_values VARCHAR(255))';
		$this -> bot -> db -> query($query);

		$query = 'CREATE TABLE IF NOT EXISTS ' . $this -> bot -> db -> define_tablename('preferences', 'true');
		$query .= '(ID INT NOT NULL AUTO_INCREMENT PRIMARY KEY, ';
		$query .= 'pref_id INT NOT NULL, ';
		$query .= 'owner INT, ';
		$query .= 'value VARCHAR(25))';
		$this -> bot -> db -> query($query);

		$this -> register_event('connect'); //Cache all defaults on connect.
		$this -> register_event("buddy"); //Cache Throw out cache on logout
		$this -> register_module('prefs');

		$this -> update_table();
	}

	function update_table()
	{
		Switch($this -> bot -> db -> get_version("preferences_def"))
		{
			case 1:
				$this -> bot -> db -> update_table("preferences_def", "access", "drop",
					"ALTER TABLE #___preferences_def DROP access");
		}
		$this -> bot -> db -> set_version("preferences_def", 2);

	}

	function connect()
	{
		//Grab all defaults and put them in cache
		$query="SELECT module, name, default_value AS value FROM #___preferences_def";
		$pref_defs = $this->bot->db->select($query, MYSQL_ASSOC);

		$this -> cache['def'] = array();
		if(!empty($pref_defs))
		{
			foreach($pref_defs as $preference)
			{
				$this -> cache['def'][strtolower($preference['module'])][strtolower($preference['name'])]=$preference['value'];
			}
		}
	}

	/*
	Buddy handling
	*/
	function buddy($name,  $msg)
	{
		$uid = $this->bot->core('player')->id($name);
		if($msg == 0)
		{
			//Buddy logging of. Throw out the cached data.
			unset($this->cache[$uid]);
		}
		else if($msg == 1)
		{
			//cache costomized preferences.
			$query = "SELECT value, module, name FROM #___preferences AS t1 JOIN #___preferences_def AS t2 ON t1.pref_ID = t2.ID WHERE owner=$uid";
			$result=$this->bot->db->select($query, MYSQL_ASSOC);
			if(!empty($result))
			{
				foreach($result as $preference)
				{
					$this->cache[$uid][strtolower($preference['module'])][strtolower($preference['name'])]=$preference['value'];
				}
			}
		}
	}

	/*
	Create a new preference definition
	*/
	function create ($module, $name, $description, $default, $possible_values)
	{
		//Condition the variables (ucfirst(strtolower()))
		$module = ucfirst(strtolower($module));
		$description = mysql_real_escape_string($description);
		$default = ucfirst(strtolower($default));

		$query = "SELECT * FROM #___preferences_def WHERE module = '$module' AND name = '$name' LIMIT 1";
		$prefs = $this -> bot -> db -> select($query);
		if(empty($prefs))
		{
			$query = "INSERT INTO #___preferences_def VALUES (NULL, '$module', '$name', '$description', '$default', '$possible_values')";
			$this -> bot -> db -> query($query);
			$this -> bot -> log ('PREFS', 'CREATE', "Created preference '$name' for module '$module' with default value '$default'");
		}
	}

	function get($name, $module=false, $setting=false)
	{
		//Check if $name is already a uid.
		if(is_numeric($name))
		{
			$uid = $name;
		}
		else
		{
			$uid = $this -> bot -> core('chat') -> get_uid($name);
		}

		if($module == false && $setting==false)
		{
			//We're fetching a list of all preferences for a user
			$prefs = array_merge($this->cache['def'], (array)$this->cache[$uid]);
			return($prefs);
		}

		if($module != false && $setting == false)
		{
			//We're fetching a list of all preferences for a given module
			$prefs=array_merge($this->cache['def'][strtolower($module)], (array)$this->cache[$uid][strtolower($module)]);
			return($prefs);
		}

		$module = strtolower($module);
		$setting = strtolower($setting);

		//Check cache for an entry
		if(isset($this->cache[$uid][$module][$setting]))
		{
			//Setting found. Return the value.
			return($this->cache[$uid][$module][$setting]);
		}
		else
		{
			//No user preference cached. Grab the default.
			return ($this->cache['def'][$module][$setting]);
		}
	}

	/*
	Change preference for module $module named $setting to $value for user $name
	*/
	function change($name, $module, $setting, $value)
	{
		$uid = $this->bot->core('chat')->get_uid($name);
		$module = strtolower($module);
		$setting = strtolower($setting);
		//Get the value this setting already has.
		$default = $this->cache['def'][$module][$setting];
		$old_value=$this->get($uid, $module, $setting);
		if($old_value instanceof BotError)
		{
			$this -> error = $old_value;
			return($this->error);
		}
		else
		{
			if($old_value == $value)
			{
				//No changes to be made so say we've already made them.
				return("Preference for $name, {$module}->{$setting} was already set to '$value'. Nothing changed.");
			}
			elseif($value == $default)
			{
				//Changing to the default value. Remove from preference table and user cache.
				$query="DELETE FROM #___preferences WHERE owner = $uid AND pref_id = (SELECT ID FROM #___preferences_def WHERE module = '$module' AND name = '$setting' LIMIT 1) LIMIT 1";
				$this->bot->db->query($query);
				unset($this->cache[$uid][$module][$setting]);
				return ("Preferences for $name, {$module}->{$setting} reset to default value '$value'");
			}
			elseif($old_value == $default)
			{
				//The value was previously set to default. An entry need to be made in the table
				$query = "INSERT INTO #___preferences (pref_id, owner, value) VALUES ((SELECT ID FROM #___preferences_def WHERE module='$module' AND name='$setting' LIMIT 1), $uid, '$value')";
				$this->bot->db->query($query);
				$this->cache[$uid][$module][$setting]=$value;
				return("Preference was created for $name, {$module}->{$setting} = $value");
			}
			else
			{
				//Neither old nor new value are defaults. An update need to be made to the table.
				$query="UPDATE #___preferences SET value='$value' WHERE owner=$uid AND pref_id=(SELECT ID FROM #___preferences_def WHERE module='$module' AND name='$setting' LIMIT 1) LIMIT 1";
				$this->bot->db->query($query);
				$this->cache[$uid][$module][$setting]=$value;
				return ("Preferences for $name, {$module}->{$setting} changed to '$value'");
			}
		}
	}

	/*
	Changes the default value of a preference
	*/
	function change_default($name, $module, $setting, $value)
	{
		$module = strtolower($module);
		$setting = strtolower($setting);
		//Update the table
		$query = "UPDATE #___preferences_def SET default_value = '$value' WHERE module='$module' AND name='$setting' LIMIT 1";
		$this -> bot -> db -> query($query);
		//Remove custom preferences for this module->setting
		//$query = "DELETE FROM #___preferences WHERE pref_id=(SELECT ID FROM #___preferences_def WHERE module='$module' AND name='$setting' LIMIT 1)";
		//Update the cache.
		$this->cache['def'][$module][$setting]=$value;
		//Remove any customisation for this cached entry
		//foreach($this->cache as &$user)
		//{
		//	if(isset($user[$module][$setting]))
		//	{
		//		unset($user[$module][$setting]);
		//	}
		//}
		//unset($user);
		$this -> bot -> log ("PREFS", "CHANGE", "$name changed the default value for setting $module -> $setting to $value");
		return("The default value for {$module}->{$setting} has been set to '$value'.");
	}

	function exists($module, $setting)
	{
		return(isset($this->cache['def'][strtolower($module)][strtolower($setting)]));
	}

	function show_modules($name)
	{
		$list = $this -> bot -> core("prefs") -> get($name);
		$list = array_keys($list);
		foreach($list as $module)
		{
			$window .= "Preferences for ".$this -> bot -> core("tools") -> chatcmd("preferences show ".$module, $module)."<br>";
		}
		return($this -> bot -> core("tools") -> make_blob('Preferences', $window));
	}

	function show_prefs($name, $module, $defaults=TRUE)
	{
		//Show preferences for the given module
		//Grab some values from the definitions
		$query = "SELECT name, description, default_value, possible_values FROM #___preferences_def WHERE module='$module'";
		$pref_defs = $this->bot->db->select($query, MYSQL_ASSOC);
		//Grab current settings from the cache.
		$prefs = $this->bot->core('prefs')->get($name, $module);

		//Create a nice header for the window
		$window = "<center>##blob_title##::: Preferences for $module :::##end##</center>\n";
		foreach($pref_defs as $preference)
		{
			//Condition values for easier access later.
			$current_value = $prefs[$preference['name']];
			$value_list = explode(';', $preference['possible_values']);

			$window .= "##highlight##{$preference['name']}: ##end####blob_text##{$preference['description']}##end##\n";

			//Create a list of buttons for each option
			$buttonlist = '##highlight##[ ##end##';
			foreach($value_list as $option)
			{
				//Check if this is the current value.
				if($option == $current_value)
				{
					$buttonlist.=$option;
				}
				else
				{
					//Create a link that enables this option to be chosen.
					$buttonlist.=$this -> bot -> core("tools") -> chatcmd("preferences set $module {$preference['name']} $option", $option);
				}
				//Check if user is able to set this value as default
				if($defaults && $this->bot->core('access_control')->check_rights($name, 'preferences', 'preferences default', 'tell'))
				{
					//Check if this is the current default
					if($option == $preference['default_value'])
					{
						$buttonlist .= '##green##[##end##';
						$buttonlist .= "D";
						$buttonlist .= '##green##]##end##';
					}
					else
					{
						$buttonlist .= '##red##[##end##';
						$buttonlist .= $this -> bot -> core("tools") -> chatcmd("preferences default $module {$preference['name']} $option", "D");
						$buttonlist .= '##red##]##end##';
					}
				}
				//add a separator
				$buttonlist.=' | ';
			}
			//Remove last ' | ' and replace it with a ' ]'
			$buttonlist = substr($buttonlist, 0, -3);
			$buttonlist .= "##highlight## ]##end##<br><br>";
			$window .= $buttonlist;
		}
		return('Preferences for ' . $this -> bot -> core("tools") -> make_blob ($module, $window));
	}
}
?>