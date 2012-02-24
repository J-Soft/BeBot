<?php
/*
* About.php - Gives info about the bot.
*
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2012 J-Soft and the BeBot development team.
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
$about = new About($bot);
/*
The Class itself...
*/
class About extends BaseActiveModule
{

	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct($bot, get_class($this));
		//Sed default access control levels
		$this->register_command('all', 'about', 'GUEST');
		$this->register_alias('about', 'version');
		$this->help['description'] = "Shows information about the bot.";
		$this->help['command']['about'] = "See description";
	}

	/*
	Unified message handler
	*/
	function command_handler($name, $msg, $origin)
	{
		$return = false;
		/*
		This should really be moved to the bot core.. but until i get the time to modify every single module... :\
		*/
		$vars = explode(' ', strtolower($msg));
		$command = $vars[0];
		switch ($command)
		{
			case 'about':
				return $this->about_blob();
				break;
			default:
				return "Broken plugin, recieved unhandled command: $command";
		}
	}

	/*
	Makes the about-blob
	*/
	function about_blob()
	{
		$version = BOT_VERSION_NAME . " v." . BOT_VERSION;
		$inside .= "##blob_text##Bot Client:##end##\n";
		$inside .= "$version\n\n";
		$inside .= "##blob_text##Developers:##end##\n";
		$inside .= "Alreadythere (RK2)\n";
		$inside .= "Blondengy (RK1)\n";
		$inside .= "Blueeagl3 (RK1)\n";
		$inside .= "Glarawyn (RK1)\n";
		$inside .= "Khalem (RK1)\n";
		$inside .= "Naturalistic (RK1)\n";
		$inside .= "Temar (RK1 / Doomsayer)\n\n";
		$inside .= "##blob_text##Special thanks to:##end##\n";
		$inside .= "Akarah (RK1)\n";
		$inside .= "Bigburtha (RK2) aka Craized\n";
		$inside .= "Derroylo (RK2)\n";
		$inside .= "Foxferal (RK1)\n";
		$inside .= "Jackjonez (RK1)\n";
		$inside .= "Sabkor (RK1)\n";
		$inside .= "Vhab (RK1)\n";
		$inside .= "Wolfbiter (RK1)\n";
		$inside .= "Xenixa (RK1)\n";
		$inside .= "Zacix (RK2)\n";
		$inside .= "Zarkingu (RK2)\n";
		$inside .= "Auno for writing and maintaining the PHP AOChat library\n";
		$inside .= "And last but not least, the greatest MMORPG community in existence.\n\n";
		$inside .= "##blob_text##Links:##end##\n";
		$inside .= $this->bot->core("tools")->chatcmd("http://bebot.shadow-realm.org", "BeBot website and support forums", "start") . "\n";
		$inside .= $this->bot->core("tools")->chatcmd("http://bugs.launchpad.net/bebot", "BeBot bugtracker", "start") . "\n";
		$return = "$version ::: " . $this->bot->core("tools")->make_blob('More details', $inside);
		return $return;
	}
}
?>