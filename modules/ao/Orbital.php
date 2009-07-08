<?php
/*
* Module parsing Org Msg to catch orbital bombardememnts and set timers to notify when those are ready again.
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
$orbital = new Orbital($bot);
class Orbital extends BasePassiveModule
{

	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));
		$this->register_event("gmsg", "Org Msg");
		$classid = $this->bot->core("timer")->create_timer_class("OrbitalWarning", "Notify class used for timer on orbitals.");
		$nextid = $this->bot->core("timer")->create_timer_class_entry($classid, - 2, 0, "", ", hit them again");
		$nextid = $this->bot->core("timer")->create_timer_class_entry($classid, $nextid, 60, "", "in one minute");
		$nextid = $this->bot->core("timer")->create_timer_class_entry($classid, $nextid, 300, "", "in five minutes");
		$nextid = $this->bot->core("timer")->create_timer_class_entry($classid, $nextid, 600, "", "in 10 minutes");
		$nextid = $this->bot->core("timer")->create_timer_class_entry($classid, $nextid, 900, "", "in 15 minutes");
	}

	function gmsg($name, $group, $msg)
	{
		if (preg_match('/Blammo! (.+) has launched an orbital attack!/i', $msg, $info))
		{
			$this->bot->core("timer")->add_timer(false, $info[1], 60 * 15 + 1, "One type of orbital strike is ready again for " . $this->bot->core("shortcuts")->get_short($this->bot->guildname), "gc", 0, "OrbitalWarning");
		}
	}
}
?>
