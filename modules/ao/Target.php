<?php
/*
* Target.php - Calls a target.
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
$target = new Target($bot);
/*
The Class itself...
*/
class Target extends BaseActiveModule
{

	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));
		$this->register_command("all", "target", "LEADER");
		if ($this->bot->guildbot)
		{
			$def = "both";
		}
		else
		{
			$def = "pgmsg";
		}
		$this->bot->core("settings")->create("Target", "Channel", $def, "Which channel should be used for output of the target spam?", "pgmsg;gc;both");
		$this->help['description'] = 'Calls a target';
		$this->help['command']['target'] = "Calls for attack on <target>.";
	}

	function command_handler($name, $msg, $origin)
	{
		$this->call_target($name, $msg);
		return false;
	}

	/*
	Makes the message
	*/
	function call_target($name, $msg)
	{
		$msg = explode(" ", $msg);
		$message = "";
		for ($i = 1; $i < count($msg); $i ++)
			$message .= $msg[$i] . " ";
		$inside = "<font color=CCInfoHeadline>:::: ASSIST TARGET ::::</font>\n\n";
		$inside .= " - <a href='chatcmd:///macro $name /assist $name'>Make assist macro</a>\n\n";
		$inside .= " - <a href='chatcmd:///assist $name'>Assist $name</a>\n\n";
		$this->bot->send_output($name, "ALL ASSIST <font color=#ffff00>$name</font>! " . "Target is <font color=#ff1111>&gt;&gt;<font color=#ffff00> $message</font>&lt;&lt;</font> :: " . $this->bot->core("tools")->make_blob("click for more", $inside), $this->bot->core("settings")->get("target", "channel"));
	}
}
?>
