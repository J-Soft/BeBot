<?php
/*
* GUI to set the levels for the access control of the bot.
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
$accesscontrol_gui = new AccessControlGUI($bot);
/*
The Class itself...
*/
class AccessControlGUI extends BaseActiveModule
{
	private $access_levels;
	private $access_shorts;
	private $channels;

	function __construct(&$bot)
	{
		parent::__construct(&$bot, get_class($this));
		$this->access_levels = $this->bot->core("access_control")->get_access_levels();
		$this->access_shorts = array('AN' => 'ANONYMOUS' , 'G' => 'GUEST' , 'M' => 'MEMBER' , 'L' => 'LEADER' , 'A' => 'ADMIN' , 'SA' => 'SUPERADMIN' , 'O' => 'OWNER' , 'D' => 'DISABLED');
		$this->shortcuts = array();
		foreach ($this->access_shorts as $short => $long)
		{
			$this->shortcuts[$long] = $short;
		}
		$this->channels = array("gc" => "##green##" , "pgmsg" => "##white##" , "tell" => "##seablue##");
		/*
		Create default access right for "commands" by SUPERADMIN if it is not set or set to DISABLED. You always want to be able to change the rights!
		*/
		if ($this->bot->core("access_control")->get_min_access_level("commands") == OWNER + 1)
		{
			$this->bot->core("access_control")->update_access("commands", "tell", "OWNER");
		}
		$this->register_command("all", "channel", "SUPERADMIN");
		$this->register_command("all", "commands", "SUPERADMIN");
		$this->help['description'] = "Allows you to set access controls for all commands in any channel.";
		$this->help['command']['commands'] = "Shows the GUI for setting access controls";
		$this->help['command']['channel'] = "Shows the current lock status for commands in guild chat and private chat group.";
		$this->help['command']['channel [lock|unlock] [gc|pgmsg]'] = "Locks or unlocks access to commands in guild chat or private chat group.";
	}

	/*
	This gets called on a tell with the command
	*/
	function command_handler($name, $msg, $origin)
	{
		if (preg_match("/^commands$/i", $msg))
			return $this->show_channels();
		elseif (preg_match("/^commands (gc|pgmsg|tell|all|extpgmsg)$/i", $msg, $info))
			return $this->show_levels($name, $info[1]);
		elseif (preg_match("/^commands subs ([a-z01-9]+)/i", $msg, $info))
			return $this->show_sub_levels($name, $info[1]);
		elseif (preg_match("/^commands update (gc|pgmsg|tell|extpgmsg|all) ([a-z01-9]+) ([a-zA-Z]+)$/i", $msg, $info))
			return $this->update_level($info[1], $info[2], $info[3]);
		elseif (preg_match("/^commands update (gc|pgmsg|tell|extpgmsg|all) ([a-z01-9]+) ([a-z01-9]+) ([a-zA-Z]+)$/i", $msg, $info))
			return $this->update_level($info[1], $info[2], $info[4], $info[3]);
		elseif (preg_match("/^commands add (gc|pgmsg|tell|extpgmsg|all) ([a-z01-9]+) ([a-z01-9]+) ([a-zA-Z]+)$/i", $msg, $info))
			return $this->update_level($info[1], $info[2], $info[4], $info[3]);
		elseif (preg_match("/^commands (del|rem) (gc|pgmsg|tell|extpgmsg|all) ([a-z01-9]+) ([a-z01-9]+)$/i", $msg, $info))
			return $this->update_level($info[2], $info[3], "DELETED", $info[4]);
		elseif (preg_match("/^commands save (.+?) (.+)$/i", $msg, $info))
			return $this->save($info[1], $info[2]);
		elseif (preg_match("/^commands load (.+)$/i", $msg, $info))
			return $this->load($info[1]);
		elseif (preg_match("/^commands saves$/i", $msg, $info))
			return $this->saves();
		elseif (preg_match("/^commands saves (rem|del) (.+)$/i", $msg, $info))
			return $this->del_save($info[2]);
		elseif (preg_match("/^channel (lock|unlock) (gc|pgmsg)$/i", $msg, $info))
			return $this->channel_lock($info[2], strtolower($info[1]) == "lock");
		elseif (preg_match("/^channel$/i", $msg, $info))
			return $this->show_channel_locks();
		else
			return $this->bot->core("tools")->chatcmd("http://bebot.shadow-realm.org/wiki/doku.php?id=commands", "Help", "start") . " for <pre>commands";
	}

	/*
	Show the channels with commands of this bot:
	*/
	function show_channels()
	{
		$blob = "##ao_infotext##The following channels contain commands:##end##\n";
		if ($this->bot->guildbot && ! (empty($this->bot->commands["gc"])))
			$blob .= "\n" . $this->bot->core("tools")->chatcmd("commands gc", "Guild Channel");
		if (! (empty($this->bot->commands["pgmsg"])))
			$blob .= "\n" . $this->bot->core("tools")->chatcmd("commands pgmsg", "Private Chatgroup");
		if (! (empty($this->bot->commands["tell"])))
			$blob .= "\n" . $this->bot->core("tools")->chatcmd("commands tell", "Tells");
		if (! (empty($this->bot->commands)))
			$blob .= "\n" . $this->bot->core("tools")->chatcmd("commands all", "All");
		if (! (empty($this->bot->commands["extpgmsg"])))
			$blob .= "\n\n" . $this->bot->core("tools")->chatcmd("commands extpgmsg", "External chatgroups");
		$blob .= "\n\n" . $this->bot->core("tools")->chatcmd("commands saves", "Saved Access Control Levels");
		return $this->bot->core("tools")->make_blob("Select a channel", $blob);
	}

	/*
	Shows the commands with current rights in the selected channel:
	*/
	function show_levels($name, $channel)
	{
		$title = "Current access levels for ";
		$blob = " ##yellow## ::: ##end## ##ao_infotext##The current access levels for ";
		$channel = strtolower($channel);
		switch ($channel)
		{
			case "gc":
				$blob .= "Guild Chat";
				$title .= "Guild Chat";
				break;
			case "pgmsg":
				$blob .= "Private Chatgroup";
				$title .= "Private Chatgroup";
				break;
			case "tell":
				$blob .= "Tells";
				$title .= "Tells";
				break;
			case "extpgmsg":
				$blob .= "External Chatgroups";
				$title .= "External Chatgroups";
				break;
			case "all":
				$blob .= "All";
				$title .= "All";
				break;
		}
		$blob .= " ##yellow## ::: ##end##";
		$blob .= "<br>Click on an access level to change it for that command##end##<br><br>";
		$blob .= "List of shortcuts:";
		foreach ($this->access_shorts as $key => $val)
		{
			$blob .= "<br>" . $key . " = " . $val;
		}
		$blob .= "<br>";
		if ($channel == "all")
		{
			$blob .= "<br>Color code for channel information:";
			foreach ($this->channels as $chan => $color)
			{
				$blob .= "<br>" . $color . $chan . "##end##";
			}
			$blob .= "<br>";
		}
		if ($channel !== "all")
		{
			if (empty($this->bot->commands[$channel]))
			{
				return "No commands defined in this channel!";
			}
		}
		else
		{
			if (empty($this->bot->commands["gc"]) && empty($this->bot->commands["pgmsg"]) && empty($this->bot->commands["tell"]))
			{
				return "No commands defined!";
			}
		}
		if ($channel !== "all")
			$sql = "AND channel = '" . $channel . "' ";
		else
			$sql = "";
		$result = $this->bot->db->select("SELECT command, subcommand, channel, minlevel FROM #___access_control WHERE minlevel != 'DELETED' " . $sql . "ORDER BY command ASC", MYSQL_ASSOC);
		if (empty($result))
		{
			if ($channel !== "all")
				$chanmsg = " for this channel";
			return "No access levels defined" . $chanmsg . "!";
		}
		foreach ($result as $right)
		{
			if ($right["subcommand"] !== "*")
			{
				$subs[$right["command"]] = TRUE;
			}
			else
			{
				$rights[$right["command"]][$right["channel"]] = $right["minlevel"];
			}
		}
		unset($result);
		foreach ($rights as $command => $right)
		{
			$isset = FALSE;
			if ($channel !== "all")
			{
				if (isset($this->bot->commands[$channel][$command]))
				{
					$isset = TRUE;
				}
			}
			else
			{
				if (isset($this->bot->commands['gc'][$command]) || isset($this->bot->commands['pgmsg'][$command]) || isset($this->bot->commands['tell'][$command]))
				{
					$isset = TRUE;
				}
			}
			if ($isset)
			{
				$blob .= "<br>##highlight##<pre>{$command}##end##:";
				$blob .= $this->Make_access_string($command, $right, $channel);
				if (isset($subs[$command]))
				{
					$blob .= "<br>&#8226; ";
					$blob .= $this->bot->core("tools")->chatcmd("commands subs " . $command, "Subcommands for " . $command);
				}
			}
		}
		Return ($this->bot->core("tools")->make_blob($title, $blob));
	}

	function show_sub_levels($name, $command)
	{
		$command = strtolower($command);
		$title = "Current access levels for " . $command . " Subcommands";
		$blob = " ##yellow## ::: ##end## ##ao_infotext##The current access levels for " . $command . " Subcommands";
		$blob .= " ##yellow## ::: ##end##";
		$blob .= "<br>Click on an access level to change it for that command##end##<br><br>";
		$blob .= "List of shortcuts:";
		foreach ($this->access_shorts as $key => $val)
		{
			$blob .= "<br>" . $key . " = " . $val;
		}
		$blob .= "<br>";
		if (empty($this->bot->commands['gc'][$command]) && empty($this->bot->commands['pgmsg'][$command]) && empty($this->bot->commands['tell'][$command]))
		{
			return "command ##highlight##" . $command . "##end## Does not Exist!";
		}
		$result = $this->bot->db->select("SELECT subcommand, channel, minlevel FROM #___access_control WHERE command = '" . $command . "' AND subcommand != '*' AND minlevel != 'DELETED' ORDER BY subcommand ASC", MYSQL_ASSOC);
		if (empty($result))
		{
			return "No Subcommand access levels defined for ##highlight##" . $command . "##end##!";
		}
		foreach ($result as $right)
		{
			$rights[$right["channel"]][$right["subcommand"]] = array($right["channel"] => $right["minlevel"]);
		}
		unset($result);
		foreach ($rights as $channel => $value)
		{
			// Only show subcommands if the command for this channel exists at all:
			if (isset($this->bot->commands[$channel][$command]))
			{
				$blob .= "\n:: " . $channel . " ::\n";
				foreach ($value as $subcommand => $right)
				{
					$blob .= "##highlight##{$command} {$subcommand}##end##:" . $this->Make_access_string($command . " " . $subcommand, $right, $channel);
					$blob .= " [" . $this->bot->core("tools")->chatcmd("commands del $channel $command $subcommand", "DEL");
					$blob .= "]<br>";
				}
			}
		}
		Return ($this->bot->core("tools")->make_blob($title, $blob));
	}

	function Make_access_string($command, $current_level, $channel)
	{
		if ($channel == "all")
		{
			foreach ($this->channels as $chan => $color)
			{
				if (isset($this->bot->commands[$chan][$command]) && isset($current_level[$chan]))
				{
					$return .= $color . " [";
					$return .= $this->shortcuts[$current_level[$chan]] . "]##end##";
				}
				else
				{
					$return .= $color . " [N/A]##end##";
				}
			}
			$current_level = "";
		}
		else
			$current_level = $current_level[$channel];
		$return .= " [ ";
		$acsstr = array();
		foreach ($this->access_levels as $level)
		{
			if ($level !== "DELETED")
			{
				if ($current_level == $level)
					$acsstr[] = $this->shortcuts[$level];
				else
					$acsstr[] = $this->bot->core("tools")->chatcmd("commands update $channel $command " . $this->shortcuts[$level], $this->shortcuts[$level]);
			}
		}
		$return .= implode(" | ", $acsstr);
		$return .= " ]";
		return $return;
	}

	/*
	Does some sanity checks before updating the minimal access level.
	*/
	function update_level($channel, $command, $newlevel, $subcommand = FALSE)
	{
		$channel = strtolower($channel);
		$command = strtolower($command);
		$newlevel = strtoupper($newlevel);
		$subcommand = strtolower($subcommand);
		// if strlen = 2 assume it's an shortcut:
		if (strlen($newlevel) <= 2)
		{
			// and replace with the full version:
			$newlevel = $this->access_shorts[$newlevel];
		}
		// Make sure only an existing access level can be selected:
		if (! in_array($newlevel, $this->access_levels))
		{
			return "Invalid access level selected!";
		}
		// Make sure you cannot disabled "commands" in tells at all - you don't want to lock yourself out from the bot!
		if (($channel == "tell" || $channel == "all") && $command == "commands" && $newlevel == "DISABLED")
		{
			return "You cannot disable the commands management at all! You don't want to lock yourself out from the bot!";
		}
		if ($channel == "all")
		{
			if ($subcommand)
			{
				$this->bot->core("access_control")->update($command, $subcommand, "gc", $newlevel);
				$this->bot->core("access_control")->update($command, $subcommand, "tell", $newlevel);
				$this->bot->core("access_control")->update($command, $subcommand, "pgmsg", $newlevel);
				return "Minimal access level to use##highlight## " . $command . " " . $subcommand . "##end## in##highlight## All Channels##end## set to##highlight## " . $newlevel . "##end##";
			}
			else
			{
				$this->bot->core("access_control")->update_access($command, "gc", $newlevel);
				$this->bot->core("access_control")->update_access($command, "tell", $newlevel);
				$this->bot->core("access_control")->update_access($command, "pgmsg", $newlevel);
				return "Minimal access level to use##highlight## " . $command . "##end## in##highlight## All Channels##end## set to##highlight## " . $newlevel . "##end##";
			}
		}
		if ($subcommand)
		{
			$this->bot->core("access_control")->update($command, $subcommand, $channel, $newlevel);
			return "Minimal access level to use##highlight## " . $command . " " . $subcommand . "##end## in##highlight## " . $channel . "##end## set to##highlight## " . $newlevel . "##end##";
		}
		else
		{
			$this->bot->core("access_control")->update_access($command, $channel, $newlevel);
			return "Minimal access level to use##highlight## " . $command . "##end## in##highlight## " . $channel . "##end## set to##highlight## " . $newlevel . "##end##";
		}
	}

	function save($name, $desc)
	{
		$result = $this->bot->db->select("SELECT name FROM #___access_control_saves WHERE name = '" . mysql_escape_string($name) . "'");
		if (! empty($result))
			Return ("##error##Error: ##highlight##" . $name . "##end## Already Exists, Please Choose a Different name or Delete old one##end##");
		else
		{
			$counts = $this->bot->core("access_control")->save($name, $desc);
			Return ("Current Access Control Levels saved as ##highlight##" . $name . "##end##. (" . $counts[0] . " Commands, " . $counts[1] . " SubCommands)");
		}
	}

	function load($name)
	{
		$result = $this->bot->db->select("SELECT name FROM #___access_control_saves WHERE name = '" . mysql_escape_string($name) . "'");
		if (empty($result))
			Return ("##error##Error: ##highlight##" . $name . "##end## does not Exist##end##");
		else
		{
			$counts = $this->bot->core("access_control")->load($name);
			Return ("##highlight##" . $name . "##end## Access Control Levels loaded. (" . $counts[0] . " Commands, " . $counts[1] . " SubCommands)");
		}
	}

	function saves()
	{
		$result = $this->bot->db->select("SELECT name, description FROM #___access_control_saves");
		if (! empty($result))
		{
			$inside = "##blob_title##  :::  Saved Access Control Levels  :::##end##\n";
			foreach ($result as $list)
			{
				$inside .= "\nName: ##blob_title##" . $list[0] . "##end##";
				$inside .= "   " . $this->bot->core("tools")->chatcmd("commands load " . $list[0], "Load");
				$inside .= "   " . $this->bot->core("tools")->chatcmd("commands saves del " . $list[0], "Delete");
				$inside .= "\nDescription: " . $list[1] . "\n";
			}
			Return ("Saved Access Control Levels ::: " . $this->bot->core("tools")->make_blob("Click to view", $inside));
		}
		else
			Return ("No Saved Access Control Levels found");
	}

	function del_save($name)
	{
		$result = $this->bot->db->select("SELECT name FROM #___access_control_saves WHERE name = '" . mysql_escape_string($name) . "'");
		if (empty($result))
			Return ("##error##Error: ##highlight##" . $name . "##end## does not Exist##end##");
		else
		{
			$this->bot->db->query("DELETE FROM #___access_control_saves WHERE name = '" . mysql_escape_string($name) . "'");
			Return ("##highlight##" . $name . "##end## Deleted.");
		}
	}

	function channel_lock($channel, $lock)
	{
		$channel = strtolower($channel);
		$lock = $lock === true;
		$msg = "##error##Error!##end##";
		if ($channel == "gc")
		{
			$this->bot->core("settings")->save("AccessControl", "LockGc", $lock);
			$msg = "All commands in##highlight## guild chat##end## are now ";
			if ($lock)
			{
				$msg .= "##red##locked from use##end##!";
			}
			else
			{
				$msg .= "##green##free to be used##end##!";
			}
		}
		elseif ($channel == "pgmsg")
		{
			$this->bot->core("settings")->save("AccessControl", "LockPgroup", $lock);
			$msg = "All commands in##highlight## private group##end## are now ";
			if ($lock)
			{
				$msg .= "##red##locked from use##end##!";
			}
			else
			{
				$msg .= "##green##free to be used##end##!";
			}
		}
		return $msg;
	}

	function show_channel_locks()
	{
		$msg = "Access to commands in##highlight## guild chat##end## is ";
		if ($this->bot->core("settings")->get("AccessControl", "LockGc"))
		{
			$msg .= "##red##locked##end##. ";
		}
		else
		{
			$msg .= "##green##unlocked##end##. ";
		}
		$msg .= "Access to commands in##highlight## private group##end## is ";
		if ($this->bot->core("settings")->get("AccessControl", "LockPgroup"))
		{
			$msg .= "##red##locked##end##.";
		}
		else
		{
			$msg .= "##green##unlocked##end##.";
		}
		return $msg;
	}
}
?>