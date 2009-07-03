<?php
/*
* Shutdown.php - Shuts bot down and restarts it.
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
* File last changed at $LastChangedDate: 2009-03-09 01:58:35 +0000 (Mon, 09 Mar 2009) $
* Revision: $Id: Shutdown.php 3 2009-03-09 01:58:35Z temar $
*/

$sdrs = new Shutdown($bot);

/*
The Class itself...
*/
class Shutdown extends BaseActiveModule
{
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));
	
		$this -> register_command("tell", "shutdown", "SUPERADMIN");	
		$this -> register_command("tell", "restart", "SUPERADMIN");

		$this -> help['description'] = 'Handles bot shut down and restart..';
		$this -> help['command']['shutdown']="Shuts down the bot.";
		$this -> help['command']['restart'] = "Restarts the bot.";
		$this -> help['notes'] = "If the bot is started in debug mode input _might_ be required in the console for the bot to restart.";
	}



	/*
	This gets called on a tell with the command
	*/
	function command_handler($name, $msg, $origin)
	{
		if(time() < $this -> bot -> connected_time + 10)
		{
			//ignore commands for 1st 10 secs to prevent unwanted restart command while offline
			Return;
		}
		$msg = explode(" ", $msg, 2);
		Switch($msg[0])
		{
			case 'shutdown':
				$this -> stop($name, "has been shutdown.", $msg[1]);
				Break;
			case 'restart':
				$this -> stop($name, "is restarting.", $msg[1]);
				Break;
			Default:
				Return "##error##Error: Shutdown Module Recieved Unknown Command ##highlight##$msg[0]##end####end##";
		}
		return false;
	}

	function stop($name, $text, $why)
	{
		if(!empty($why))
			$why = " (".$why.")";
		$this -> bot -> send_irc("", "", "The bot " . $text . $why);
		$this -> bot -> send_gc("The bot " . $text . $why);
		$this -> bot -> send_pgroup("The bot " . $text . $why);
		$this -> bot -> send_tell($name, "The bot " . $text);

		$this -> crontime = array (time() + 2, "The bot " . $text);
		$this -> register_event("cron", "1sec");
	}

	function cron()
	{
		if ($this -> crontime[0] <= time())
		{
			$this -> bot -> disconnect();
			die($this -> crontime[1] . "\n");
		}
	}
}
?>