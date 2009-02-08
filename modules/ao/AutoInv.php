<?php
/*
 * AutoInv.php - Module to auto invite members.
 *
* BeBot - An Anarchy Online & Age of Conan Chat Automaton
* Copyright (C) 2004 Jonas Jax
* Copyright (C) 2005-2007 Thomas Juberg Stens�s, ShadowRealm Creations and the BeBot development team.
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
* File last changed at $LastChangedDate: 2008-11-30 23:09:06 +0100 (Sun, 30 Nov 2008) $
* Revision: $Id: AutoInv.php 1833 2008-11-30 22:09:06Z alreadythere $
 */

$autoinv = new AutoInv($bot);

/*
The Class itself...
*/
class AutoInv extends BaseActiveModule
{
	/*
	Constructor:
	Hands over a referance to the "Bot" class.
	*/
	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));

		$this -> bot -> core("settings") -> create("AutoInv", "Activated", FALSE, "Is the autoinvite activated?");
		$this -> bot -> core("settings") -> create("AutoInv", "ShowInfo", TRUE, "Should an informative message telling how to disable autoinvite be sent to all characters that are invited via autoinvite?");
		
		$this -> bot -> core('prefs') -> create('AutoInv', 'recieve_auto_invite', 'Automatic invites to private group should be?', 'Off', 'Off;On');

		$this -> register_command("tell", "autoinvite", "GUEST");

		// Register for logon notifies
		$this -> register_event("logon_notify");

		$this -> help['description'] = "Automatically invites players to the private group.";
		$this -> help['command']['autoinvite'] = 'Shows your current auto-invite status';
		$this -> help['command']['autoinvite <(on|off)>'] = "Turns automatic invitation for you on or off.";
		$this -> help['notes']='You can also change your status by using preferences.';
	}

	function command_handler($name, $msg, $origin)
	{
		$com = $this->parse_com($msg, array('com', 'state'));
		switch($com['state'])
		{
			case '':
				return($this->get_status($name));
				break;
			case 'on':
				return($this->enable_invite($name));
				break;
			case 'off':
				return($this->disable_invite($name));
				break;
			default:
				$this->bot->send_help($name, 'autoinvite');
		}
	}

	function get_status($name)
	{
		return($this->bot->core('prefs')->get($name, 'AutoInv', 'recieve_auto_invite'));
	}
	
	function enable_invite($name)
	{
		$this->bot->core('prefs')->change($name, 'AutoInv', 'recieve_auto_invite', 'On');
		return('Autoinvite has been enabled');
	}
	
	function disable_invite($name)
	{
		$this->bot->core('prefs')->change($name, 'AutoInv', 'recieve_auto_invite', 'Off');
		return('Autoinvite has been disabled');
	}

	// Compare the user level of $name with the setting for who should be autoinvited
	function check_access($name)
	{
		$userlevel = $this -> bot -> db -> select("SELECT user_level FROM #___users WHERE nickname = '$name'", MYSQL_ASSOC);
		if (empty($userlevel))
		{
			return false;
		}
		$userlevel = $userlevel[0]['user_level'];
		switch ($userlevel)
		{
			case 2:
				if (strtolower($this -> bot -> core("settings") -> get("Members", "AutoInviteGroup")) == 'members'
				|| strtolower($this -> bot -> core("settings") -> get("Members", "AutoInviteGroup")) == 'both')
				{
					return true;
				}
				break;
			default:
				if (strtolower($this -> bot -> core("settings") -> get("Members", "AutoInviteGroup")) == 'guests'
				|| strtolower($this -> bot -> core("settings") -> get("Members", "AutoInviteGroup")) == 'both')
				{
					return true;
				}
				break;
		}
		return false;
	}

	function notify($user, $startup = false)
	{
		if ($this -> bot -> core("settings") -> get("Autoinv", "Activated"))
		{
			if ($this->bot->core('prefs')->get($user, 'AutoInv', 'recieve_auto_invite')=='On'
			&& $this -> check_access($user) && !($this -> bot -> core("online") -> in_chat($user)))
			{
				if ($this -> bot -> core("settings") -> get("AutoInv", "ShowInfo"))
				{
					$blob = $this -> bot -> core("tools") -> chatcmd("autoinvite off", "Click here to remove yourself from autoinvite");
					$this -> bot -> send_tell($user, "If you don't want this bot to invite you in the future, click " . $this -> bot -> core("tools") -> make_blob('here', $blob) . " or type: /tell <botname> <pre>autoinvite off");
				}
				$this -> bot -> core("chat") -> pgroup_invite($user);
			}
		}
	}
}
?>
