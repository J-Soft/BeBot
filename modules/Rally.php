<?php
/*
* Rally.php - Sets a rallying point for raids.
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
* Revision: $Id: Rally.php 1833 2008-11-30 22:09:06Z alreadythere $
*/


$rally = new Rally($bot);


/*
The Class itself...
*/
class Rally extends BaseActiveModule
{
	var $rallyinfo;

	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> rallyinfo = "No rally point has been set.";

		$this -> register_command('all', 'rally', 'MEMBER');

		$this -> help['description'] = 'Sets a rallying point for the raid.';
		$this -> help['command']['rally']="Shows the current rally point.";
		$this -> help['command']['rally <playfield> <x-coord> <y-coord>']="Sets a rally point in playfield <playfield> at <x-coord> X <y-coord>";
		$this -> help['command']['rally clear'] = "Clear the rally pont.";
		$this -> help['note']="<playfield> may also be the last parameter given.";
	}



	function command_handler($name, $msg, $origin)
	{
		if (preg_match("/^rally$/i", $msg))
		return $this -> rallyinfo;
		else if (preg_match("/^rally (rem|del|clear)$/i", $msg, $info))
		return $this -> del_rally($name);
		else if (preg_match("/^rally ([a-zA-Z0-9]+) ([0-9]+) ([0-9]+)$/i", $msg, $info))
		return $this -> set_rally($info[1], $info[2], $info[3], "");
		else if (preg_match("/^rally ([a-zA-Z0-9]+) ([0-9]+) ([0-9]+) (.*)$/i", $msg, $info))
		return $this -> set_rally($info[1], $info[2], $info[3], $info[4]);
	}



	/*
	Make the rally info
	*/
	function set_rally($zone, $x, $y, $note)
	{
		$this -> rallyinfo = "Rally info: [<font color=#ffffff>Zone:</font> <font color=#ffff00>$zone</font>] " .
		"[<font color=#ffffff>Coords:</font> <font color=#ffff00>$x, $y</font>] " .
		"[<font color=#ffffff>Note:</font><font color=#ffff00> $note</font>]";
		return "Rally point has been set.";
	}



	/*
	Remove the rally info
	*/
	function del_rally($name)
	{
		if ($this -> bot -> core("security") -> check_access($name, "LEADER"))
		{
			$this -> rallyinfo = "No rally point has been set.";
			return "Rally has been cleared.";
		}
		else
		return "You must be a LEADER or higher to clear the rally point.";
	}
}
?>