<?php
/*
 * Utillities for handling units, profession names and shortcuts.
 * Written by Alreadythere (RK2).
*
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
$profession_core = new Profession_Core($bot);
class Profession_Core extends BasePassiveModule
{
	// array with profession name as key and shortcut as value
	private $cache = array();
	private $units = array();

	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
		$this->bot->db->query("DROP TABLE IF EXISTS #___professions");
		$this->register_module("professions");
		if ($this->bot->game == "aoc")
		{
			$this->cache = array('Barbarian' => 'barb' , 'Conqueror' => 'conq' , 'Guardian' => 'guard' , 'Priest of Mitra' => 'pom' , 'Tempest of Set' => 'tos' , 'Bear Shaman' => 'bs' , 'Dark Templar' => 'dt' , 'Assassin' => 'ass' , 'Ranger' => 'rang' , 'Necromancer' => 'necro' , 'Herald of Xotli' => 'hox' , 'Demonologist' => 'demo');
		}
		else
		{
			$this->cache = array('Adventurer' => 'adv' , 'Agent' => 'agent' , 'Bureaucrat' => 'crat' , 'Doctor' => 'doc' , 'Enforcer' => 'enf' , 'Engineer' => 'eng' , 'Fixer' => 'fixer' , 'Keeper' => 'keeper' , 'Martial Artist' => 'ma' , 'Meta-Physicist' => 'mp' , 'Nano-Technician' => 'nt' , 'Shade' => 'shade' , 'Soldier' => 'sol' , 'Trader' => 'trader');
			$this->units = array('artillery' => array('Adventurer' , 'Agent' , 'Fixer' , 'Soldier' , 'Trader') , 'control' => array('Bureaucrat' , 'Engineer' , 'Meta-Physicist' , 'Trader') , 'extermination' => array('Bureaucrat' , 'Meta-Physicist' , 'Nano-Technician') , 'infantry' => array('Adventurer' , 'Enforcer' , 'Keeper' , 'Martial Artist') , 'support' => array('Adventurer' , 'Doctor' , 'Fixer' , 'Keeper' , 'Martial Artist' , 'Meta-Physicist' , 'Trader'));
		}
	}

	// Returns full name of $shortcut,
	// Returns correct cased full name if $shortcut already is a full name
	// Returns BotError if it's neither
	function full_name($shortcut)
	{
		$this->error->reset();
		$shortcut = strtolower($shortcut);
		//Get an array with lower-cased profession names as keys.
		$lc_cache = array_change_key_case($this->cache);
		// Is it a shortcut?
		if ($full_name = array_search($shortcut, $this->cache))
		{
			return ($full_name);
		}
		// Or a full name?
		elseif (isset($lc_cache[$shortcut]))
		{
			return (array_search($lc_cache[$shortcut], $this->cache));
		}
		// error otherwise
		else
		{
			$this->error->set("##highlight##'$shortcut'##end## is not a valid profession name or shortcut.");
			return ($this->error);
		}
	}

	// Returns the shortcut of $profession
	// Returns $profession is already a shortcut
	// Returns BotError if it's neither
	function shortcut($profession)
	{
		$this->error->reset();
		$profession = str_replace('-', ' ', ucfirst(strtolower($profession)));
		//Is it a valid profession
		if (isset($this->cache[$profession]))
		{
			return ($this->cache[$profession]);
		}
		//Check if $profession is a valid shortcut.
		elseif (in_array($profession, $this->cache))
		{
			return ($profession);
		}
		else
		{
			$this->error->set("'$profession' is not a valid profession name or shortcut.");
			return ($this->error);
		}
	}

	//Returns a list of professions separated by $separator
	function get_professions($separator = ', ')
	{
		return (implode($separator, array_keys($this->cache)));
	}

	//Returns an array with professions
	function get_profession_array()
	{
		return (array_values(array_flip($this->cache)));
	}

	function get_shortcuts($separator = ', ')
	{
		return (implode($separator, $this->cache));
	}

	//Returns an array with shortcuts
	function get_shortcut_array()
	{
		return (array_values($this->cache));
	}

	//Returns an array with all units
	function get_unit_array()
	{
		return (array_keys($this->units));
	}

	//Returns all units $profession is a member of
	//$profession can be a full name or a short hand.
	function get_units($profession)
	{
		if (($profession = $this->full_name($profession)) instanceof BotError)
			return $profession;
		$prof_units = array();
		foreach ($this->units as $unit => $professions_array)
		{
			if (in_array($profession, $professions_array))
			{
				$prof_units[] = $unit;
			}
		}
		return $prof_units;
	}

	//Gets a list of units that $profession is a member of separated by $separator
	function get_unit_list($profession, $separator = ', ')
	{
		return (implode($separator, $this->get_units($profession)));
	}
}
?>