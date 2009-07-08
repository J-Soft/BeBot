<?php
/*
* Oe.php - Module to calculation over-equipping.
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
/*
Add a "_" at the beginning of the file (_ClassName.php) if you do not want it to be loaded.
*/
$oe = new Oe($bot);
class Oe extends BaseActiveModule
{

	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));
		$this->register_command("all", "oe", "GUEST");
		$this->help['description'] = 'Module for calculating over-equipping';
		$this->help['command']['oe <level>'] = "Shows the minimum skill level required to use an item with <level> requirement and ";
		$this->help['command']['oe <level>'] .= "shows the maximum skill level requirement an item can have if your skill level is <level>";
	}

	function command_handler($name, $msg, $origin)
	{
		if (preg_match("/^oe (.+)$/i", $msg, $info))
		{
			return $this->calc_oe($info[1]);
		}
		else
		{
			$this->bot->send_help($name);
		}
		return false;
	}

	/*
	Calculate OE reqs
	*/
	function calc_oe($oe)
	{
		return "With a skill of <font color=#ffff00>" . (int) $oe . "</font>, you will be OE above <font color=#ffff00>" . (int) ($oe / 0.8) . "</font> skill. " . "With a requirement of <font color=#ffff00>" . (int) $oe . "</font> skill, you can have <font color=#ffff00>" . (int) ($oe * 0.8) . "</font> without being OE.";
	}
}
?>
