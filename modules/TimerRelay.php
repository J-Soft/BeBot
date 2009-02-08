<?php
/*
* Backend to relay timers.
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
* File last changed at $LastChangedDate$
* Revision: $Id: TimerRelay.php 1833 2008-11-30 22:09:06Z alreadythere $
*/

$timerrelay = new TimerRelay($bot);

/*
The Class itself...
*/
class TimerRelay extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> register_command("tell", "relaytimer", "SUPERADMIN");
		$this -> register_command("extpgmsg", "relaytimer", "MEMBER");
	}

	function extpgmsg($pgroup, $name, $msg)
	{
		$this -> command_handler($pgroup, $msg, "extpgmsg");
	}

	function command_handler($name, $msg, $origin)
	{
		if ($this -> bot -> core("settings") -> get('Relay', 'Status') &&
		strtolower($this -> bot -> core("settings") -> get('Relay', 'Relay')) == strtolower($name))
		{
			if (preg_match("/^relaytimer class:(.*) endtime:(.*) owner:(.*) repeat:(.*) channel:(.*) name:(.*)/$i", $msg, $info))
			{
				$this -> add_timer($info[3], $info[2], $info[6], $info[1], $info[4], $info[5]);
			}
		}
		return false;
	}

	function add_timer($owner, $endtime, $name, $class, $repeat, $channel)
	{
		$this -> bot -> core("timer") -> add_timer(true, $owner, $endtime - time(), $name, $channel, $repeat, $class);
	}
}
?>