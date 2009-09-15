<?php
/*
* Rally.php - Sets a rallying point for raids.
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
$rally = new Rally($bot);
/*
The Class itself...
*/
class Rally extends BaseActiveModule
{
	var $rallyinfo;

	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));

		$this -> rallyinfo = FALSE;

		$this -> register_command('all', 'rally', 'MEMBER');

		$this->help['description'] = 'Sets a rallying point for the raid.';
		$this->help['command']['rally'] = "Shows the current rally point.";
		$this->help['command']['rally <playfield> <x-coord> <y-coord> <notes>'] = "Sets a rally point in playfield <playfield> at <x-coord> X <y-coord>, <notes> is optional";
		$this->help['command']['rally clear'] = "Clear the rally pont.";
		$this->help['note'] = "<playfield> may also be the last parameter given.";
	}

	function command_handler($name, $msg, $origin)
	{
		$msg = explode(" ", $msg, 2);
		if(strtolower($msg[0]) != "rally")
			Return("Error Unknown Command ##highlight##$msg[0]##end## in Rally Module");
		$msg = trim($msg[1]);
		if (!$msg || $msg == "")
			return $this -> get_rally();
		else if (preg_match("/^(rem|del|clear)$/i", $msg, $info))
			return $this -> del_rally($name);
		else if (preg_match("/^([ a-zA-Z0-9]+) ([0-9]+) ([0-9]+)$/i", $msg, $info))
			return $this -> set_rally($info[1], $info[2], $info[3], "");
		else if (preg_match("/^([ a-zA-Z0-9]+) ([0-9]+) ([0-9]+) (.*)$/i", $msg, $info))
			return $this -> set_rally($info[1], $info[2], $info[3], $info[4]);
		else if (preg_match("/^- ([0-9].+), ([0-9].+), ([0-9].+) \(([0-9].+) ([0-9].+) y ([0-9].+) ([0-9]+)\)$/i", $msg, $info))
			return $this -> set_rally($info[7], $info[1], $info[2], "");
		else if (preg_match("/^- ([0-9].+), ([0-9].+), ([0-9].+) \(([0-9].+) ([0-9].+) y ([0-9].+) ([0-9]+)\) (.*)$/i", $msg, $info))
			return $this -> set_rally($info[7], $info[1], $info[2], $info[8]);
		else
			return ("To set Rally: <pre>rally &lt;playfield&gt; &lt;x-coord&gt; &lt;y-coord&gt; &lt;notes&gt;");
	}

	/*
	Make the rally info
	*/
	function set_rally($zone, $x, $y, $note)
	{
		if(is_numeric($zone))
		{
			$zonenum = $this -> bot -> db -> select("SELECT area FROM #___land_control_zones WHERE zoneid = $zone");
			if(!empty($zonenum))
			{
				$zonenum = $zone;
				$zone = $zonenum[0][0];
				$e = "and Way";
			}
			else
				$zonenum = FALSE;
		}
		else
		{
			$zonenum = $this -> bot -> db -> select("SELECT zoneid FROM #___land_control_zones WHERE area = '".mysql_real_escape_string($zone)."' OR short = '".mysql_real_escape_string($zone)."'");
			if(!empty($zonenum))
			{
				$zonenum = $zonenum[0][0];
				$e = "and Way";
			}
			else
				$zonenum = FALSE;
		}
		$this -> rallyinfo = array($zone, $x, $y, $note, $zonenum);
		return "Rally ".$e."point has been set.";
	}

	function get_rally()
	{
		$rally = $this -> rallyinfo;
		if($rally)
		{
			$return = "Rally info: [<font color=#ffffff>Zone:</font> <font color=#ffff00>$rally[0]</font>] " .
			"[<font color=#ffffff>Coords:</font> <font color=#ffff00>$rally[1], $rally[2]</font>] " .
			"[<font color=#ffffff>Note:</font><font color=#ffff00> $rally[3]</font>]";
			if($rally[4])
			{
				$inside = " :: Rally info::<br><font color=#ffffff>Zone:</font> <font color=#ffff00>$rally[0]</font><Br>" .
				"<font color=#ffffff>Coords:</font> <font color=#ffff00>$rally[1], $rally[2]</font><br>" .
				"<font color=#ffffff>Note:</font><font color=#ffff00> $rally[3]</font><br><br>".
				$this -> bot -> core("tools") -> chatcmd($rally[1]." ".$rally[2]." ".$rally[4], "Set Waypoint", "waypoint");
				$return .= " :: ".$this -> bot -> core("tools") -> make_blob("Click for Waypoint", $inside);
			}
			Return($return);
		}
		else
			Return "No rally point has been set.";
	}

	/*
	Remove the rally info
	*/
	function del_rally($name)
	{
		if ($this->bot->core("security")->check_access($name, "LEADER"))
		{
			$this -> rallyinfo = FALSE;
			return "Rally has been cleared.";
		}
		else
			return "You must be a LEADER or higher to clear the rally point.";
	}
}
?>